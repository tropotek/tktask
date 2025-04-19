<?php
namespace App\Controller\TaskCategory;

use App\Db\TaskCategory;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Table\Action\Csv;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;
use Tk\Table\Action\Delete;
use Tk\Uri;
use Tk\Db;

class Manager extends ControllerAdmin
{
    protected ?Table $table = null;

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Task Category Manager', 'fa fa-folder-open');

        $this->setUserAccess(User::PERM_SYSADMIN);

        // init table
        $this->table = new Table();
        $this->table->setOrderBy('order_by');
        $this->table->setLimit(25);

        $this->table->appendCell(Cell\OrderBy::create('orderBy', TaskCategory::class));

        $rowSelect = RowSelect::create('id', 'taskCategoryId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('name')
            ->addHeaderCss('text-start')
            ->addCss('text-nowrap')
            ->addOnValue(function(TaskCategory $obj, Cell $cell) {
                $url = Uri::create('/taskCategoryEdit', ['taskCategoryId' => $obj->taskCategoryId]);
                return sprintf('<a href="%s">%s</a>', $url, $obj->name);
            });

        $this->table->appendCell('description')
            ->addHeaderCss('max-width text-start');

        $this->table->appendCell('active')
            ->addCss('text-nowrap')
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        $this->table->getForm()->appendField((new Select('active', ['' => '-- All --', 'y' => 'Active', 'n' => 'Inactive'])))
            ->setValue('y');

        // Add Table actions
        $this->table->appendAction(\Tk\Table\Action\Select::create('Active Status', 'fa fa-fw fa-times')
            ->setActions(['Active' => 'active', 'Disable' => 'disable'])
            ->setConfirmStr('Toggle active/disable on the selected rows?')
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnSelect(function(\Tk\Table\Action\Select $action, array $selected, string $value) {
                foreach ($selected as $id) {
                    $obj = TaskCategory::find($id);
                    $obj->active = (strtolower($value) == 'active');
                    $obj->save();
                }
            })
        );

        $this->table->appendAction(Csv::create()
            ->addOnCsv(function(Csv $action) {
                $action->setExcluded(['actions']);
                if (!$this->table->getCell(TaskCategory::getPrimaryProperty())) {
                    $this->table->prependCell(TaskCategory::getPrimaryProperty())->setHeader('id');
                }
                $this->table->getCell('name')->getOnValue()->reset();

                $filter = $this->table->getDbFilter()->resetLimits();
                return TaskCategory::findFiltered($filter);
            }));

        // execute table to init filter object
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $rows = TaskCategory::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-body" var="actions">
      <a href="/taskCategoryEdit" title="Create Task Category" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Task Category</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i var="icon"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}