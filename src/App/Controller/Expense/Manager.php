<?php
namespace App\Controller\Expense;

use App\Db\Expense;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Alert;
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
        Breadcrumbs::reset();
        $this->getPage()->setTitle('Expense Manager');
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
            ->addOnValue(function(Expense $obj, Cell $cell) {
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
                $url = Uri::create('/companyEdit')->set('companyId', $obj->companyId);
                $str = e($obj->getCompany()->name);
                return <<<HTML
                    <a href="$url" target="_blank" title="Company Edit">$str</a>
                HTML;
            });

        $this->table->appendCell('total')
            ->addCss('text-nowrap text-end')
            ->setSortable(true);

        $this->table->appendCell('invoiceNo')
            ->addCss('text-nowrap')
            ->setSortable(true);

        $this->table->appendCell('receiptNo')
            ->addCss('text-nowrap')
            ->setSortable(true);

        $this->table->appendCell('categoryId')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue(function(Expense $obj, Cell $cell) {
                return $obj->getExpenseCategory()->name;
            });

        $this->table->appendCell('purchasedOn')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::onValue');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');


        // Add Table actions
        $this->table->appendAction(Delete::create()
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnDelete(function(Delete $action, array $selected) {
                foreach ($selected as $expense_id) {
                    Db::delete('expense', compact('expense_id'));
                }
            }));

        $this->table->appendAction(Csv::create()
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnCsv(function(Csv $action, array $selected) {
                $action->setExcluded(['actions']);
                if (!$this->table->getCell(Expense::getPrimaryProperty())) {
                    $this->table->prependCell(Expense::getPrimaryProperty())->setHeader('id');
                }
                $this->table->getCell('description')->getOnValue()->reset();
                $this->table->getCell('companyId')->getOnValue()->reset();
                $this->table->getCell('companyId')->addOnValue(function(Expense $obj, Cell $cell) {
                    return $obj->getCompany()->name;
                });

                $filter = $this->table->getDbFilter();
                if ($selected) {
                    $rows = Expense::findFiltered($filter);
                } else {
                    $rows = Expense::findFiltered($filter->resetLimits());
                }
                return $rows;
            }));

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
      <a href="/expenseEdit" title="Create Expense" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Expense</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-money-check-alt"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}