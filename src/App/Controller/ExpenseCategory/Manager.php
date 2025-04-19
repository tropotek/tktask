<?php
namespace App\Controller\ExpenseCategory;

use App\Db\ExpenseCategory;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Form\Field\Input;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;
use Tk\Table\Action\Csv;
use Tk\Table\Action\Delete;
use Tk\Table\Action\Select;
use Tk\Uri;
use Tk\Db;

class Manager extends ControllerAdmin
{
    protected ?Table $table = null;

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Expense Category Manager', 'fa fa-folder-open');

        $this->setUserAccess(User::PERM_SYSADMIN);

        // init table
        $this->table = new Table();
        $this->table->setOrderBy('name');
        $this->table->setLimit(25);

        $rowSelect = RowSelect::create('id', 'expenseCategoryId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('name')
            ->setSortable(true)
            ->addCss('text-nowrap')
            ->addHeaderCss('max-width')
            ->addOnValue(function(ExpenseCategory $obj, Cell $cell) {
                $url = Uri::create('/expenseCategoryEdit', ['expenseCategoryId' => $obj->expenseCategoryId]);
                return sprintf('<a href="%s">%s</a>', $url, $obj->name);
            });

        $this->table->appendCell('ratio')
            ->setHeader('Claim %')
            ->setSortable(true)
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(ExpenseCategory $obj, Cell $cell) {
                return round($obj->claim * 100) . '%';
            });

        $this->table->appendCell('active')
            ->setSortable(true)
            ->addCss('text-nowrap text-center')
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        $this->table->appendCell('modified')
            ->setSortable(true)
            ->addCss('text-nowrap')
            ->addOnValue('\Tk\Table\Type\DateFmt::onValue');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        $this->table->getForm()->appendField((new \Tk\Form\Field\Select('active', ['' => '-- All --', 'y' => 'Active', 'n' => 'Inactive'])))
            ->setValue('y');


        // Add Table actions
        $this->table->appendAction(Select::create('Active Status', 'fa fa-fw fa-times')
            ->setActions(['Active' => 'active', 'Disable' => 'disable'])
            ->setConfirmStr('Toggle active/disable on the selected rows?')
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnSelect(function(Select $action, array $selected, string $value) {
                foreach ($selected as $id) {
                    $obj = ExpenseCategory::find($id);
                    $obj->active = (strtolower($value) == 'active');
                    $obj->save();
                }
            })
        );

        $this->table->appendAction(Csv::create()
            ->addOnCsv(function(Csv $action) {
                $action->setExcluded(['actions']);
                if (!$this->table->getCell(ExpenseCategory::getPrimaryProperty())) {
                    $this->table->prependCell(ExpenseCategory::getPrimaryProperty())->setHeader('id');
                }
                $this->table->getCell('name')->getOnValue()->reset();
                $filter = $this->table->getDbFilter();
                return ExpenseCategory::findFiltered($filter->resetLimits());
            }));

        // execute table
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $rows = ExpenseCategory::findFiltered($filter);
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
    <div class="card-body" var="actions">
      <a href="/expenseCategoryEdit" title="Create Expense Category" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Category</a>
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