<?php
namespace App\Component;

use App\Db\Invoice;
use App\Db\Payment;
use App\Db\User;
use Bs\Mvc\ComponentInterface;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Db;
use Tk\Log;

class PaymentTable extends \Dom\Renderer\Renderer implements ComponentInterface
{
    protected Table    $table;
    protected ?Invoice $invoice  = null;


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $invoiceId = (int)($_REQUEST['invoiceId'] ?? 0);
        $this->invoice = Invoice::find($invoiceId);

        if (!($this->invoice instanceof Invoice)) {
            Log::error("invalid invoice ID {$invoiceId}");
            return null;
        }

        // init table
        $this->table = new Table('payments-tbl');
        $this->table->hideReset();
        $this->table->setOrderBy('-created');
        $this->table->setLimit(25);
        $this->table->addCss('tk-table-sm');

        $this->table->appendCell('method')
            ->addHeaderCss('text-start')
            ->addCss('max-width text-nowrap')
            ->setSortable(true);

        $this->table->appendCell('amount')
            ->addCss('text-end');

        $this->table->appendCell('created')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\DateTime::onValue');

        // execute table
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $filter['invoiceId'] = $invoiceId;
        $rows = Payment::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());

        return $this->show();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $this->table->getRenderer()->setFooterEnabled(false);
        $template->appendTemplate('content', $this->table->htmxShow());

        return $template;
    }


    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-money-bill"></i> <span var="title">Invoice Payments</span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}
