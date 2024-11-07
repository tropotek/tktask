<?php
namespace App\Controller\Company;

use App\Db\Company;
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

/**
 *
 */
class Manager extends ControllerAdmin
{
    protected ?Table $table = null;

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Company Manager');

        // init table
        $this->table = new \Bs\Mvc\Table();
        $this->table->setOrderBy('company_id');
        $this->table->setLimit(25);

        $rowSelect = RowSelect::create('id', 'companyId');
        $this->table->appendCell($rowSelect);

//        $this->table->appendCell('actions')
//            ->addCss('text-nowrap text-center')
//            ->addOnValue(function(Company $Company, Cell $cell) {
//                $url = Uri::create('/companyEdit')->set('companyId', $Company->companyId);
//                return <<<HTML
//                    <a class="btn btn-outline-success" href="$url" title="Edit"><i class="fa fa-fw fa-edit"></i></a>
//                HTML;
//            });

//        $this->table->appendCell('companyId')
//            ->addCss('text-nowrap')
//            ->setSortable(true);

        $this->table->appendCell('name')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addHeaderCss('max-width')
            ->addOnValue(function(\App\Db\Company $obj, Cell $cell) {
                $url = Uri::create('/companyEdit', ['companyId' => $obj->companyId]);
                return sprintf('<a href="%s">%s</a>', $url, $obj->name);
            });

        $this->table->appendCell('contact')
            ->addCss('text-nowrap')
            ->setSortable(true);

        $this->table->appendCell('phone')
            ->addCss('text-nowrap')
            ->setSortable(true);

        $this->table->appendCell('email')
            ->addCss('text-nowrap')
            ->setSortable(true);

        $this->table->appendCell('website')
            ->addCss('text-nowrap')
            ->setSortable(true);

//        $this->table->appendCell('alias')
//            ->addCss('text-nowrap')
//            ->setSortable(true);

//        $this->table->appendCell('abn')
//            ->addCss('text-nowrap')
//            ->setSortable(true);

//        $this->table->appendCell('address')
//            ->addCss('text-nowrap')
//            ->setSortable(true);

//        $this->table->appendCell('credit')
//            ->addCss('text-nowrap')
//            ->setSortable(true);

        $this->table->appendCell('type')
            ->addCss('text-nowrap')
            ->setSortable(true);

//        $this->table->appendCell('modified')
//            ->addCss('text-nowrap')
//            ->setSortable(true)
//            ->addOnValue('\Tk\Table\Type\DateFmt::onValue');

        $this->table->appendCell('created')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\DateFmt::onValue');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        $this->table->getForm()->appendField((new Select('type', Company::TYPE_LIST))->prependOption('-- Type --', ''))
            ->setValue(Company::TYPE_CLIENT);

        // init filter fields for actions to access to the filter values
        $this->table->initForm();

        // Add Table actions
        $this->table->appendAction(Delete::create($rowSelect))
            ->addOnDelete(function(Delete $action, array $selected) {
                foreach ($selected as $company_id) {
                    Db::delete('company', compact('company_id'));
                }
            });

        $this->table->appendAction(Csv::create($rowSelect))
            ->addOnCsv(function(Csv $action, array $selected) {
                $action->setExcluded(['id', 'actions']);
                $filter = $this->table->getDbFilter();
                if ($selected) {
                    $rows = Company::findFiltered($filter);
                } else {
                    $rows = Company::findFiltered($filter->resetLimits());
                }
                return $rows;
            });

        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $rows = Company::findFiltered($filter);
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
      <a href="/companyEdit" title="Create Company" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Company</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-building"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}