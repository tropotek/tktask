<?php
namespace App\Controller\Expense;

use App\Db\Company;
use App\Db\Expense;
use App\Db\ExpenseCategory;
use App\Db\Team;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Collection;
use Tk\Db;
use Tk\Form\Field\Input;
use Tk\Table\Action\ColumnSelect;
use Tk\Table\Action\Csv;
use Tk\Table\Action\Delete;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;
use Tk\Uri;

class Manager extends ControllerAdmin
{
    protected ?Table $table = null;

    public function doDefault(): void
    {
        Breadcrumbs::reset();
        $this->getPage()->setTitle('Expense Manager', 'fas fa-money-check-alt');
        $this->validateAccess(User::getAuthUser()?->isStaff() ?? false);

        // init table
        $this->table = new Table();
        $this->table->setOrderBy('-purchased_on');
        $this->table->setLimit(25);

        $rowSelect = RowSelect::create('id', 'expenseId');
        $this->table->appendCell($rowSelect);

//        $this->table->appendCell('actions')
//            ->addCss('text-nowrap text-center')
//            ->addOnValue(function(Expense $obj, Cell $cell) {
//                $url = Uri::create('/expenseEdit')->set('expenseId', $obj->expenseId);
//                return <<<HTML
//                    <a class="btn btn-outline-success" href="$url" title="Edit"><i class="fa fa-fw fa-edit"></i></a>
//                HTML;
//            });

        $this->table->appendCell('description')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnHtml(function(Expense $obj, Cell $cell) {
                $url = Uri::create('/expenseEdit')->set('expenseId', $obj->expenseId);
                $description = e($obj->description);
                return <<<HTML
                    <a href="$url" title="Edit">$description</a>
                HTML;
            });

        $this->table->appendCell('companyId')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue(function(Expense $obj, Cell $cell) {
                return $obj->getCompany()->name;
            })
            ->addOnHtml(function(Expense $obj, Cell $cell) {
                $url = Uri::create('/companyEdit')->set('companyId', $obj->companyId);
                $str = e($obj->getCompany()->name);
                return <<<HTML
                    <a href="$url" title="Company Edit">$str</a>
                HTML;
            });

//        $this->table->appendCell('invoiceNo')
//            ->addCss('text-nowrap')
//            ->setSortable(true);

//        $this->table->appendCell('receiptNo')
//            ->addCss('text-nowrap')
//            ->setSortable(true);

        $this->table->appendCell('expenseCategoryId')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue(function(Expense $obj, Cell $cell) {
                return $obj->getExpenseCategory()->name;
            });

        $this->table->appendCell('purchasedOn')
            ->addHeaderCss('text-end')
            ->addCss('text-nowrap text-end')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::getLongDate');

        $this->table->appendCell('total')
            ->addHeaderCss('text-end')
            ->addCss('text-nowrap text-end')
            ->setSortable(true);


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        $cats = Company::findFiltered(Db\Filter::create(['type' => Company::TYPE_SUPPLIER, 'active' => true], 'name'));
        $list = Collection::toSelectList($cats, 'companyId', fn($obj) => ($obj->active ? '' : '- ') . $obj->name);
        $this->table->getForm()->appendField((new \Tk\Form\Field\Select('companyId', $list))
            ->prependOption('-- Company --', ''));

        $cats = ExpenseCategory::findFiltered(Db\Filter::create(['active' => true], 'name'));
        $list = Collection::toSelectList($cats, 'expenseCategoryId', fn($obj) => ($obj->active ? '' : '- ') . $obj->name);
        $this->table->getForm()->appendField((new \Tk\Form\Field\Select('expenseCategoryId', $list))
            ->prependOption('-- Category --', ''));


        // Add Table actions
        $this->table->appendAction(ColumnSelect::create());
        $this->table->appendAction(Delete::createDefault(Expense::class, $rowSelect));
        $this->table->appendAction(Csv::createDefault(Expense::class, $rowSelect));


        // execute table
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $rows = Expense::findFiltered($filter);
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
      <a href="/expenseEdit" title="Create Expense" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Expense</a>
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