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
use Tk\Table\Action\ColumnSelect;
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
        $this->getPage()->setTitle('Invoice Manager', 'far fa-credit-card');
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
            ->addOnHtml(function(Invoice $obj, Cell $cell) {
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
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue(function(Invoice $obj, Cell $cell) {
                return Invoice::STATUS_LIST[$obj->status];
            })
            ->addOnHtml(function(Invoice $obj, Cell $cell) {
                return sprintf('<span class="badge text-bg-%s">%s</span>',
                    Invoice::STATUS_CSS[$obj->status],
                    $cell->getValue($obj)
                );
            });

        $this->table->appendCell('total')
            ->addHeaderCss('text-end')
            ->addCss('text-nowrap text-end')
            ->setSortable(true);

        $this->table->appendCell('items')
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(Invoice $obj, Cell $cell) {
                return count($obj->getItemList());
            });

        $this->table->appendCell('issuedOn')
            ->addHeaderCss('text-end')
            ->addCss('text-nowrap text-end')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::getLongDate');

        $this->table->appendCell('created')
            ->addHeaderCss('text-end')
            ->addCss('text-nowrap text-end')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::getLongDateTime');


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
        $this->table->appendAction(ColumnSelect::create());
        $this->table->appendAction(Csv::createDefault(Invoice::class, $rowSelect));


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
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        $template->appendTemplate('content', $this->table->show());

        $dialogId = CompanySelectDialog::CONTAINER_ID;
        $template->setAttr('create', 'data-bs-toggle', 'modal');
        $template->setAttr('create', 'data-bs-target', '#'.$dialogId);


        $js = <<<JS
jQuery(function($) {
    $(document).on('companySelect', function(e, companyId, name) {
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
    <div class="card-body" var="actions">
      <a href="#" title="Create Invoice" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Invoice</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i var="icon"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>

  <div hx-get="/component/companySelectDialog" hx-trigger="load" hx-swap="outerHTML" var="companySelect"></div>
</div>
HTML;
        return Template::load($html);
    }

}