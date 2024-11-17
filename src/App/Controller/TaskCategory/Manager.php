<?php
namespace App\Controller\TaskCategory;

use App\Db\TaskCategory;
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
        $this->getPage()->setTitle('Task Category Manager');

        // init table
        $this->table = new \Bs\Mvc\Table();
        $this->table->setOrderBy('order_by');
        $this->table->setLimit(25);

        $this->table->appendCell(Cell\OrderBy::create('orderBy', TaskCategory::class));

        $rowSelect = RowSelect::create('id', 'taskCategoryId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('name')
            ->addCss('text-nowrap')
            ->addHeaderCss('max-width')
            ->addOnValue(function(\App\Db\TaskCategory $obj, Cell $cell) {
                $url = Uri::create('/taskCategoryEdit', ['taskCategoryId' => $obj->taskCategoryId]);
                return sprintf('<a href="%s">%s</a>', $url, $obj->name);
            });

        $this->table->appendCell('label')
            ->addCss('text-nowrap');

        $this->table->appendCell('active')
            ->addCss('text-nowrap')
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        $this->table->getForm()->appendField((new Select('active', ['-- All --' => '', 'Active' => 'y', 'Inactive' => 'n'])))
            ->setValue('y');

        // init filter fields for actions to access to the filter values
        $this->table->initForm();

        // Add Table actions
        $this->table->appendAction(\Tk\Table\Action\Select::create($rowSelect, 'Active Status', 'fa fa-fw fa-times')
            ->setActions(['Active' => 'active', 'Disable' => 'disable'])
            ->setConfirmStr('Toggle active/disable on the selected rows?')
            ->addOnSelect(function(\Tk\Table\Action\Select $action, array $selected, string $value) {
                foreach ($selected as $id) {
                    $obj = TaskCategory::find($id);
                    $obj->active = (strtolower($value) == 'active');
                    $obj->save();
                }
            })
        );

        $this->table->appendAction(Csv::create($rowSelect))
            ->addOnCsv(function(Csv $action, array $selected) {
                $action->setExcluded(['id', 'actions']);
                $filter = $this->table->getDbFilter();
                if ($selected) {
                    $rows = TaskCategory::findFiltered($filter);
                } else {
                    $rows = TaskCategory::findFiltered($filter->resetLimits());
                }
                return $rows;
            });

        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $filter->set('active', truefalse($filter['active'] ?? null));
        $rows = TaskCategory::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
      <a href="/taskCategoryEdit" title="Create Task Category" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Task Category</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-folder-open"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}