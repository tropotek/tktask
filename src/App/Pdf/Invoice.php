<?php

namespace App\Pdf;

use App\Factory;
use Bs\Registry;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Log;
use Tk\Path;

class Invoice extends PdfInterface
{
    protected ?\App\Db\Invoice $invoice = null;

    public function doDefault(): string
    {
        //@ini_set("memory_limit", "128M");

        $invoiceId = intval($_GET['invoiceId'] ?? $_POST['invoiceId'] ?? 0);
        $output    = trim($_GET['o'] ?? $_POST['o'] ?? PdfInterface::OUTPUT_PDF);

        $this->invoice = \App\Db\Invoice::find($invoiceId);
        if (!($this->invoice instanceof \App\Db\Invoice)) {
            Log::error("invalid invoice id {$invoiceId}");
            Breadcrumbs::getBackUrl()->redirect();
        }

        if (!$this->getWatermark()) {
            switch ($this->invoice->status) {
                case \App\Db\Invoice::STATUS_OPEN:
                    $this->setWatermark('Open');
                    break;
                case \App\Db\Invoice::STATUS_PAID:
                    $this->setWatermark('Paid');
                    break;
                case \App\Db\Invoice::STATUS_CANCELLED:
                    $this->setWatermark('Cancelled');
                    break;
            }
        }

        $siteCompany = Factory::instance()->getOwnerCompany();
        $this->setTitle($siteCompany->name . ' - Invoice');
        $this->mpdf->SetAuthor($siteCompany->name);
        $this->setFilename('Invoice-' . $this->invoice->invoiceId . '.pdf');

        $this->mpdf->WriteHTML($this->show()->toString());
        return match ($output) {
            PdfInterface::OUTPUT_PDF => $this->getPdf(),
            PdfInterface::OUTPUT_ATTACH => $this->getPdfAttachment(),
            default => $this->getTemplate()->toString()
        };
    }

    function show(): ?Template
    {
        $template = $this->getTemplate();

        $siteCompany = Factory::instance()->getOwnerCompany();
        $company = $this->invoice->getCompany();

        // Setup page
        $template->setTitleText('Invoice No: ' . $this->invoice->invoiceId);
        $template->setText('due-days', strval(Registry::getValue('account.due.days', \App\Db\Invoice::DEFAULT_OVERDUE_DAYS)));

        $paymentText = Registry::getValue('site.invoice.payment', '');
        if ($paymentText) {
            $template->setHtml('invoice-payment', $paymentText);
            $template->setVisible('invoice-payment');
        }

        // Render Invoice
        $template->setText('invoice-id', strval($this->invoice->invoiceId));
        $template->setText('shop-name', $siteCompany->name);
        $template->setText('shop-phone', $siteCompany->phone);
        $template->setText('shop-email', $siteCompany->email);
        $template->setHtml('shop-address', nl2br($siteCompany->address));
        if ($siteCompany->abn) {
            $template->setText('abn', 'ABN: ' . $siteCompany->abn);
            $template->setVisible('abn');
        }

        $template->setText('client-name', $company->name);
        if ($company->contact) {
            $template->setText('client-contact', $company->contact);
            $template->setVisible('v-client-contact');
        }
        $template->setText('client-phone', $company->phone);
        $template->setText('client-email', $company->email);
        if ($company->accountsEmail) {
            $template->setText('client-accountsEmail', $company->accountsEmail);
            $template->setVisible('v-client-accountsEmail');
        }

        $issued = '--';
        $due = '--';
        if ($this->invoice->issuedOn) {
            $issued = $this->invoice->issuedOn->format(\Tk\Date::FORMAT_AU_DATE);
            $due = $this->invoice->getDateDue()->format(\Tk\Date::FORMAT_AU_DATE);
        }
        $template->setText('dateIssued', $issued);
        $template->setText('date-due', $due);
        $template->setHtml('notes', $this->invoice->notes);

        if ($this->invoice->purchaseOrder) {
            $template->setText('purchaseOrder', $this->invoice->purchaseOrder);
            $template->setVisible('v-purchaseOrder');
        }

        // totals

        if ($this->invoice->discount > 0) {
            $template->setText('discount-pcnt', round($this->invoice->discount * 100, 2) . '%');
            $template->setText('discount-amount', $this->invoice->discountTotal);
            $template->setVisible('discount');
        }

        if ($this->invoice->tax > 0) {
            $template->setText('tax-pcnt', round($this->invoice->tax * 100, 2) . '%');
            $template->setText('tax-amount', $this->invoice->taxTotal);
            $template->setVisible('tax');
        }

        if ($this->invoice->shipping->getAmount() > 0) {
            $template->setText('shipping-amount', $this->invoice->shipping);
            $template->setVisible('shipping');
        }

        $template->setText('subTotal-amount', $this->invoice->total);

        if ($this->invoice->paidTotal->getAmount() > 0) {
            $template->setText('paid-amount', '-'.$this->invoice->paidTotal);
            $template->setVisible('paid');
        }

        $template->setText('total-amount', $this->invoice->unpaidTotal);

        $template->setText('status', $this->invoice->status);
        $template->addCss('status', 'badge-' . \App\Db\Invoice::STATUS_CSS[$this->invoice->status]);

        if ($this->invoice->status == \App\Db\Invoice::STATUS_UNPAID) {
            $outstanding = $this->invoice->getOutstandingAmount();
            if ($outstanding->getAmount() > 0) {
                $template->setText('outstanding-amount', $outstanding->toString());
                $template->setVisible('outstanding');
            }

            $payable = $outstanding->add($this->invoice->unpaidTotal);
            $template->setText('total-payable-amount', $payable);
            $template->setVisible('total-payable');
        }

        // render item list
        foreach ($this->invoice->getItemList() as $item) {
            $row = $template->getRepeat('item');
            $row->setAttr('item', 'data-item-id', $item->invoiceItemId);
            $row->setText('itemId', strval($item->invoiceItemId));
            $row->setText('description', $item->description);
            $row->setText('qty', strval($item->qty));
            $row->setText('productCode', $item->productCode);
            $row->setText('price', $item->price);
            $row->setText('total', $item->total);
            $row->appendRepeat();
        }

        $cssFile = Path::create('/src/App/Pdf/pdfStyles.css');
        if (is_file($cssFile)) {
            $pdfStyles = (string)file_get_contents($cssFile);
            $template->appendCss($pdfStyles);
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title></title>

  <style>
.payment-methods {
  font-family: dejavusanscondensed,arial,serif;
  font-size: 0.8em;
}
.payment-methods .payments-title {
  text-decoration: underline;
  font-weight: bold;
}
.payment-methods td {
  vertical-align: top;
  line-height: 1.4em;
  font-size: 0.8em;
}
.payment-methods td p {
    margin-top: 40px;
}

.outstanding-totals {
    width: 100%;
    font-family: serif;
    text-align: center;
}
.outstanding-totals .box {
    background-color: #dceffc;
    padding: 10px;
    width: 22%;
}
.outstanding-totals .sep {
    width: 2%;
}
.money {
  text-align: right;
}
.bak-grey {
  background-color: #efefef;
}
</style>

</head>
<body>

  <htmlpageheader name="myheader">
    <table class="w-100">
      <tr>
        <td class="w-50" style="color:#0000BB;">
          <span style="font-weight: bold; font-size: 14pt;" var="shop-name">Acme Trading Co.</span> &nbsp;
          <span style="font-family:dejavusanscondensed,serif;" var="abn" choice="abn">ABN: 01777 123 567</span>
        </td>
        <td class="text-end w-50">
          Invoice #: <span style="font-weight: bold; font-size: 12pt;" var="invoice-id">0012345</span>
        </td>
      </tr>
    </table>
  </htmlpageheader>
  <htmlpagefooter name="myfooter" style="display: none;">
    <div style=" font-size: 9pt; text-align: center; padding-top: 3mm; ">
      Page {PAGENO} of {nb}
    </div>
  </htmlpagefooter>
  <sethtmlpageheader name="myheader" value="on" show-this-page="1" />
  <sethtmlpagefooter name="myfooter" value="on" />

  <table class="tk-table w-100" style="font-family: serif; font-size: 0.9em; line-height: 1.3em;">
    <tr>
      <td style="border: 0.05mm solid #888888; width: 47%; padding: 10px; vertical-align: top;">
        <span style="font-size: 7pt; color: #555555; font-family: sans-serif;">FROM:</span><br />
        <strong var="shop-name"></strong><br />
        <span var="shop-address"></span><br />
        Phone: <span var="shop-phone"></span><br />
        Email: <span var="shop-email"></span>
      </td>
      <td style="width: 5%">&nbsp;</td>
      <td style="border: 0.05mm solid #888888; width: 47%; padding: 10px; vertical-align: top">
        <span style="font-size: 7pt; color: #555555; font-family: sans-serif;">TO:</span><br />
        <strong var="client-name"></strong>
        <span choice="v-client-contact">
            <br/><span var="client-contact"></span>
        </span>
        <br/>Phone: <span var="client-phone"></span>
        <br/>Email: <span var="client-email"></span>
        <span choice="v-client-accountsEmail">
            <br/>Accounts: <span var="client-accountsEmail"></span>
        </span>
      </td>
    </tr>
  </table>

  <table class="tk-table w-100" style="line-height: 1.4em; margin-top: 10px; margin-bottom: 0; font-family: serif;">
    <tr>
      <td style="width: 47%; vertical-align: top;">
        <div>
          <strong>Invoice #:</strong>
          <span var="invoice-id"></span>
        </div>
        <div choice="v-purchaseOrder">
          <strong>Purchase Order:</strong>
          <span var="purchaseOrder"></span>
        </div>
      </td>
      <td style="width: 5%">&nbsp;</td>
      <td style="vertical-align: top; width: 47%;">
        <div>
          <strong>Invoice date:</strong>
          <span var="dateIssued">12/04/2014</span>
        </div>
        <div>
          <strong>Due date:</strong>
          <span var="date-due">12/05/2014</span>
        </div>
      </td>
    </tr>
  </table>

  <p var="notes" style=""></p>

  <table class="p-table item-list" style="width: 100%; page-break-inside:avoid;">
    <thead>
      <tr>
        <th><span>P-Code</span></th>
        <th class="key"><span>Name</span></th>
        <th><span>#</span></th>
        <th><span>Unit</span></th>
        <th><span>Total</span></th>
      </tr>
    </thead>
    <tbody>
      <tr class="item" var="item" repeat="item">
        <td class="bb-1" var="productCode" style="font-size: 9pt;"></td>
        <td class="bb-1" var="description" style="font-size: 9pt;"></td>
        <td class="bb-1" var="qty"></td>
        <td class="money bb-1" var="price">$0.00</td>
        <td class="money bb-1" var="total">$0.00</td>
      </tr>

      <tr choice="discount">
        <td colspan="2"></td>
        <td class="text-end " colspan="2">Discount (<span var="discount-pcnt"></span>):</td>
        <td class="bak-grey money" var="discount-amount">0.00</td>
      </tr>
      <tr choice="tax">
        <td colspan="2"></td>
        <td class="text-end" colspan="2">Tax (<span var="tax-pcnt"></span>):</td>
        <td class="bak-grey money" var="tax-amount">0.00</td>
      </tr>
      <tr choice="shipping">
        <td colspan="2"></td>
        <td class="text-end" colspan="2">Shipping:</td>
        <td class="bak-grey money" var="shipping-amount">0.00</td>
      </tr>
      <tr>
        <td colspan="2"></td>
        <td class="text-end" colspan="2">Subtotal:</td>
        <td class="bak-grey money" var="subTotal-amount">0.00</td>
      </tr>
      <tr choice="paid">
        <td colspan="2"></td>
        <td class="text-end" colspan="2">Paid:</td>
        <td class="bak-grey money" var="paid-amount">$0.00</td>
      </tr>
      <tr>
        <td colspan="2"></td>
        <td class="text-end" colspan="2">Total:</td>
        <td class="bak-grey money" var="total-amount">$0.00</td>
      </tr>
    </tbody>
  </table>

  <p><small>
    All accounts are to be paid within <span var="due-days">14</span> days from receipt of invoice.
    Late payments may incur a 10% fee.
  </small></p>

  <table class="outstanding-totals">
    <tr>
      <td class="box">
        Due Date<br/>
        <span var="date-due">12/05/2014</span>
      </td>
      <td class="sep">&nbsp;</td>
      <td class="box" choice="outstanding">
        Outstanding<br/>
        <span var="outstanding-amount">$0.00</span>
      </td>
      <td class="sep" choice="outstanding">&nbsp;</td>
      <td class="box text-strong">
        Payable<br/>
        <span var="total-payable-amount">$0.00</span>
      </td>
    </tr>
  </table>

  <br/>
  <hr/>
  <div var="invoice-payment" style="page-break-inside: avoid; border: 1px solid #EFEFEF; padding: 10px;"></div>

</body>
</html>
HTML;

        return Template::load($html);
    }

}