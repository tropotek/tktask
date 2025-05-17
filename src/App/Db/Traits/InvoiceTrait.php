<?php
namespace App\Db\Traits;

use App\Db\Invoice;

trait InvoiceTrait
{
    private ?Invoice $_invoice = null;

    public function getInvoice(): ?Invoice
    {
        if (!$this->_invoice) {
            $this->_invoice = Invoice::find($this->invoiceId);
        }
        return $this->_invoice;
    }

}
