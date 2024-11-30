<?php
namespace App\Table;

use App\Db\Company;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Collection;
use Tk\Uri;
use Tk\Db;
use Tk\Table\Action\Csv;
use Tk\Table\Action\Delete;
use Tk\Form\Field\Input;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;

/**
 * Example Controller:
 * <code>
 * class Manager extends \Bs\ControllerAdmin {
 *      protected ?Table $table = null;
 *      public function doDefault(mixed $request, string $type): void
 *      {
 *          ...
 *          // init the user table
 *          $this->table = new \App\Table\Task();
 *          $this->table->setOrderBy('name');
 *          $this->table->setLimit(25);
 *          $this->table->execute();
 *          // Set the table rows
 *          $filter = $this->table->getDbFilter();
 *          $rows = User::findFiltered($filter);
 *          $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());
 *          ...
 *      }
 *      public function show(): ?Template
 *      {
 *          $template = $this->getTemplate();
 *          $template->appendTemplate('content', $this->table->show());
 *          return $template;
 *      }
 * }
 * </code>
 */
class Task extends Table
{

    public function init(): static
    {
        $editUrl = Uri::create('/taskEdit');

        $rowSelect = RowSelect::create('id', 'taskId');
        $this->appendCell($rowSelect);

        $this->appendCell('actions')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(\App\Db\Task $obj, Cell $cell) {
                $url = Uri::create('/taskEdit')->set('taskId', $obj->taskId);
                return <<<HTML
                    <a class="btn btn-outline-success" href="$url" title="Edit"><i class="fa fa-fw fa-edit"></i></a>
                HTML;
            });

        $this->appendCell('subject')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addHeaderCss('max-width')
            ->addOnValue(function(\App\Db\Task $obj, Cell $cell) {
                $url = Uri::create('/taskEdit', ['taskId' => $obj->taskId]);
                return sprintf('<a href="%s">%s</a>', $url, $obj->subject);
            });

        $this->appendCell('companyId')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue(function(\App\Db\Task $obj, Cell $cell) {
                return $obj->getCompany()?->name ?? 'N/A';
            });

        $this->appendCell('status')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue(function(\App\Db\Task $obj, Cell $cell) {
                return sprintf('<span class="badge text-bg-%s">%s</span>',
                    \App\Db\Task::STATUS_CSS[$obj->status],
                    \App\Db\Task::STATUS_LIST[$obj->status]
                );
            });

        $this->appendCell('priority')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue(function(\App\Db\Task $obj, Cell $cell) {
                return sprintf('<span class="badge text-bg-%s">%s</span>',
                    \App\Db\Task::PRIORITY_CSS[$obj->priority],
                    \App\Db\Task::PRIORITY_LIST[$obj->priority]
                );
            });

        // Progress
        // todo wait until Task exist
        $this->appendCell('progress')
            ->addCss('text-nowrap');

        $this->appendCell('minutes')
            ->setHeader('Est Min.')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue(function(\App\Db\Task $obj, Cell $cell) {
                return date('H:i', strtotime($obj->minutes));
            });

        $this->appendCell('invoiced')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\DateFmt::onValue');

        $this->appendCell('modified')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\DateFmt::onValue');

        $this->appendCell('created')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\DateFmt::onValue');


        // Add Filter Fields
        $this->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        $this->getForm()->appendField((new \Tk\Form\Field\Select('status', \App\Db\Task::STATUS_LIST))
            ->setMultiple(true)
            ->setAttr('placeholder', '-- Status --')
            ->addCss('tk-checkselect'))
            ->setPersistent(true)
            ->setValue([\App\Db\Task::STATUS_PENDING, \App\Db\Task::STATUS_HOLD, \App\Db\Task::STATUS_OPEN]);

        $cats = Company::findFiltered(Db\Filter::create(['type' => Company::TYPE_CLIENT], '-active, name'));
        $list = Collection::toSelectList($cats, 'companyId', fn($obj) => ($obj->active ? '' : '- ') . $obj->name);
        $this->getForm()->appendField((new \Tk\Form\Field\Select('companyId', $list))
            ->prependOption('-- Company --', ''));

        // init filter fields for actions to access to the filter values
        //$this->initForm();

        // Add Table actions
//        $this->appendAction(Delete::create())
//            ->addOnGetSelected([$rowSelect, 'getSelected'])
//            ->addOnDelete(function(Delete $action, array $selected) {
//                foreach ($selected as $task_id) {
//                    Db::delete('task', compact('task_id'));
//                }
//            });

        $this->appendAction(Csv::create())
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnCsv(function(Csv $action, array $selected) {
                $action->setExcluded(['id', 'actions']);
                $filter = $this->getDbFilter();
                if ($selected) {
                    $rows = \App\Db\Task::findFiltered($filter);
                } else {
                    $rows = \App\Db\Task::findFiltered($filter->resetLimits());
                }
                return $rows;
            });

        return $this;
    }

}