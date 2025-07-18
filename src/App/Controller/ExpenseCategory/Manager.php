<?php
namespace App\Controller\ExpenseCategory;

use App\Db\ExpenseCategory;
use App\Db\Team;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Form\Field\Input;
use Tk\Table\Action\ColumnSelect;
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
            ->addOnHtml(function(ExpenseCategory $obj, Cell $cell) {
                $url = Uri::create('/expenseCategoryEdit', ['expenseCategoryId' => $obj->expenseCategoryId]);
                return sprintf('<a href="%s">%s</a>', $url, $obj->name);
            });

        $this->table->appendCell('ratio')
            ->setHeader('Claim %')
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue(function(ExpenseCategory $obj, Cell $cell) {
                return round($obj->claim * 100) . '%';
            });

        $this->table->appendCell('active')
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        $this->table->appendCell('modified')
            ->addHeaderCss('text-end')
            ->addCss('text-nowrap text-end')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::getLongDateTime');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        $this->table->getForm()->appendField((new \Tk\Form\Field\Select('active', ['' => '-- All --', 'y' => 'Active', 'n' => 'Inactive'])))
            ->setValue('y');


        // Add Table actions
        $this->table->appendAction(ColumnSelect::create());
        $this->table->appendAction(\Tk\Table\Action\Select::createActiveSelect(ExpenseCategory::class, $rowSelect));
        $this->table->appendAction(Csv::createDefault(ExpenseCategory::class, $rowSelect));


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