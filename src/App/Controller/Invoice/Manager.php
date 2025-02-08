<?php
namespace App\Controller\Invoice;

use App\Component\CompanySelectDialog;
use App\Db\Company;
use App\Db\Invoice;
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
use Tk\Uri;
use Tk\Db;

class Manager extends ControllerAdmin
{
    protected ?Table $table = null;

    public function doDefault(): void
    {
        Breadcrumbs::reset();
        $this->getPage()->setTitle('Invoice Manager');
        $this->validateAccess(User::getAuthUser()?->isStaff() ?? false);

        // init table
        $this->table = new Table();
        $this->table->setOrderBy('-created');
        $this->table->setLimit(25);

        $rowSelect = RowSelect::create('id', 'invoiceId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('invoiceId')
            ->setHeader('ID')
            ->addCss('text-nowrap')
            ->setSortable(true);

        $this->table->appendCell('Client')
            ->addHeaderCss('max-width')
            ->addCss('text-nowrap')
            ->addOnValue(function(Invoice $obj, Cell $cell) {
                $model = $obj->getDbModel();
                $name = '';
                if ($model instanceof Company) {
                    $name = e($model->name);
                }
                $url = Uri::create('/invoiceEdit')->set('invoiceId', $obj->invoiceId);
                return <<<HTML
                    <a href="$url" title="Edit">{$name}</a>
                HTML;
            });

        $this->table->appendCell('status')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue(function(Invoice $obj, Cell $cell) {
                return sprintf('<span class="badge text-bg-%s">%s</span>',
                    Invoice::STATUS_CSS[$obj->status],
                    Invoice::STATUS_LIST[$obj->status]
                );
            });

        $this->table->appendCell('total')
            ->addCss('text-nowrap text-end')
            ->setSortable(true);

        $this->table->appendCell('items')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(Invoice $obj, Cell $cell) {
                return count($obj->getItemList());
            });

        $this->table->appendCell('issuedOn')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::onValue');

        $this->table->appendCell('created')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::onValue');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        $this->table->getForm()->appendField((new \Tk\Form\Field\Select('status', Invoice::STATUS_LIST))
            ->setMultiple(true)
            ->setAttr('placeholder', '-- Status --')
            ->addCss('tk-checkselect'))
            ->setPersistent(true)
            ->setValue([Invoice::STATUS_OPEN, Invoice::STATUS_UNPAID]);


        // Add Table actions
        $this->table->appendAction(Csv::create()
            ->addOnCsv(function(Csv $action) {
                $action->setExcluded(['actions']);
                if (!$this->table->getCell(Invoice::getPrimaryProperty())) {
                    $this->table->prependCell(Invoice::getPrimaryProperty())->setHeader('id');
                }
                $this->table->getCell('client')->getOnValue()->reset();
                $filter = $this->table->getDbFilter()->resetLimits();
                return Invoice::findFiltered($filter);
            }));

        // execute table
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $rows = Invoice::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->appendTemplate('content', $this->table->show());

        $dialogId = CompanySelectDialog::CONTAINER_ID;
        $template->setAttr('create', 'data-bs-toggle', 'modal');
        $template->setAttr('create', 'data-bs-target', '#'.$dialogId);


        $js = <<<JS
jQuery(function($) {
    const dialog = '#{$dialogId}';

    $(dialog).on('companySelect', function(e, companyId, name) {
        location = tkConfig.baseUrl + '/invoiceEdit?companyId=' + companyId;
    });
});
JS;
        $template->appendJs($js);


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
      <a href="#" title="Create Invoice" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Invoice</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i class="far fa-credit-card"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
  
  <div hx-get="/component/companySelectDialog" hx-trigger="load" hx-swap="outerHTML" var="companySelect"></div>
</div>
HTML;
        return Template::load($html);
    }

}