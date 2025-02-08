<?php
namespace App\Table;

use App\Component\TaskLogAddDialog;
use App\Db\Company;
use App\Db\TaskCategory;
use App\Util\Tools;
use Bs\Mvc\Table;
use Bs\Registry;
use Dom\Template;
use Tk\Collection;
use Tk\Uri;
use Tk\Db;
use Tk\Table\Action\Csv;
use Tk\Form\Field\Input;
use Tk\Table\Cell;

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
        $this->appendCell('actions')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(\App\Db\Task $obj, Cell $cell) {
                if (!$obj->isOpen()) {
                    $cell->getTable()->getRowAttrs()->addCss('task-closed');
                }
                $disabled = $obj->isOpen() ? '' : 'disabled';
                $url = Uri::create('/taskEdit')->set('taskId', $obj->taskId);
                $dialogId = TaskLogAddDialog::CONTAINER_ID;
                return <<<HTML
                    <button class="btn btn-primary $disabled" type="button" title="Add Log" $disabled
                        data-bs-target="#{$dialogId}"
                        data-bs-toggle="modal"
                        data-task-id="{$obj->taskId}">
                        <i class="far fa-fw fa-clock"></i>
                    </button>
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
            ->addCss('text-nowrap')
            ->addOnValue(function(\App\Db\Task $obj, Cell $cell) {
                $pcnt = 0;
                if ($obj->minutes) {
                    $completed = $obj->getCompletedTime();
                    $pcnt = (round(($completed/$obj->minutes), 2) * 100);
                }
                return sprintf('
                    <div class="progress" role="progressbar" aria-label="Task Progress" aria-valuenow="%s" aria-valuemin="0" aria-valuemax="100">
                      <div class="progress-bar" style="width: %s%%">%s%%</div>
                    </div>', $pcnt, $pcnt, $pcnt
                );
            });

        $this->appendCell('minutes')
            ->setHeader('Est Min.')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue(function(\App\Db\Task $obj, Cell $cell) {
                return Tools::mins2Str($obj->minutes);
            });

        if (Registry::instance()->get('site.invoice.enable', false)) {
            $this->appendCell('cost')
                ->setHeader('$ Cur.')
                ->addOnValue(function(\App\Db\Task $obj, Cell $cell) {
                    return $obj->getCost()->toString();
                });

            $this->appendCell('est')
                ->setHeader('$ Est.')
                ->addOnValue(function(\App\Db\Task $obj, Cell $cell) {
                    return $obj->getEstimatedCost()->toString();
                });
        }

        $this->appendCell('logs')
            ->addCss('text-center')
            ->addOnValue(function(\App\Db\Task $obj, Cell $cell) {
                return count($obj->getLogList());
            });

        $this->appendCell('created')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::onValue');


        // Add Filter Fields
        $this->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        $cats = TaskCategory::findFiltered(Db\Filter::create(['active' => true], 'order_by'));
        $list = Collection::toSelectList($cats, 'taskCategoryId');
        $this->getForm()->appendField((new \Tk\Form\Field\Select('categoryId', $list))
            ->prependOption('-- Category --', ''));

        $this->getForm()->appendField((new \Tk\Form\Field\Select('status', \App\Db\Task::STATUS_LIST))
            ->setMultiple(true)
            ->setAttr('placeholder', '-- Status --')
            ->addCss('tk-checkselect'))
            ->setPersistent(true)
            ->setValue([\App\Db\Task::STATUS_PENDING, \App\Db\Task::STATUS_HOLD, \App\Db\Task::STATUS_OPEN]);

        $this->getForm()->appendField((new \Tk\Form\Field\Select('priority', \App\Db\Task::PRIORITY_LIST))
            ->setMultiple(true)
            ->setAttr('placeholder', '-- Priority --')
            ->addCss('tk-checkselect'))
            ->setPersistent(true);

        $cats = Company::findFiltered(Db\Filter::create(['type' => Company::TYPE_CLIENT], '-active, name'));
        $list = Collection::toSelectList($cats, 'companyId', fn($obj) => ($obj->active ? '' : '- ') . $obj->name);
        $this->getForm()->appendField((new \Tk\Form\Field\Select('companyId', $list))
            ->prependOption('-- Company --', ''));


        // Add Table actions
        $this->appendAction(Csv::create())
            ->addOnCsv(function(Csv $action, array $selected) {
                $action->setExcluded(['id', 'actions']);
                $this->getCell('subject')->getOnValue()->reset();
                $this->getCell('status')->getOnValue()->reset();
                $this->getCell('priority')->getOnValue()->reset();
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

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $htmx = <<<HTML
<div hx-get="/component/taskLogAddDialog" hx-trigger="load" hx-swap="outerHTML"></div>
HTML;
        $template->appendHtml('table', $htmx);

        $js = <<<JS
jQuery(function ($) {
    $(document).on('tkForm:afterSubmit', function() {
        location = location.href;
    });
});
JS;
        $template->appendJs($js);

        return parent::show();
    }
}