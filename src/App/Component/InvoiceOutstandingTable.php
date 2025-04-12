<?php
namespace App\Component;

use App\Db\Invoice;
use App\Db\Payment;
use App\Db\User;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Date;
use Tk\Db;
use Tk\Log;
use Tk\Table\Cell;
use Tk\Uri;

class InvoiceOutstandingTable extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    protected Table    $table;
    protected ?Invoice $invoice  = null;


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $invoiceId = (int)($_POST['invoiceId'] ?? $_GET['invoiceId'] ?? 0);
        $this->invoice = Invoice::find($invoiceId);

        if (!($this->invoice instanceof Invoice)) {
            Log::error("invalid invoice ID {$invoiceId}");
            return null;
        }

        // init table
        $this->table = new Table('outstanding-tbl');
        $this->table->hideReset();
        $this->table->setOrderBy('-created');
        $this->table->setLimit(25);
        $this->table->addCss('tk-table-sm');

        $this->table->appendCell('issuedOn')
            ->addHeaderCss('text-start')
            ->addCss('max-width text-nowrap')
            ->addOnValue(function(Invoice $obj, Cell $cell) {
                $url = Uri::create('/invoiceEdit')->set('invoiceId', $obj->invoiceId);
                return <<<HTML
                    <a href="$url">{$obj->issuedOn->format(Date::FORMAT_LONG_DATE)}</a>
                HTML;
            });

        $this->table->appendCell('total')
            ->addCss('text-end');

        $this->table->appendCell('unpaidTotal')
            ->addCss('text-end');

        $this->table->appendCell('created')
            ->addCss('text-nowrap text-center')
            ->addOnValue('\Tk\Table\Type\DateFmt::onValue');

        // execute table
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $rows = Invoice::findOutstanding($invoiceId, $filter);
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
    <div class="card-header"><i class="fas fa-money-bill"></i> <span var="title">Outstanding Invoices</span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}
