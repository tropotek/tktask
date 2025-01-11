<?php
namespace App\Pdf;

use App\Db\Invoice;
use App\Factory;
use Bs\Registry;
use Dom\Template;
use JetBrains\PhpStorm\NoReturn;
use Mpdf\Mpdf;
use Tk\Config;
use Tk\Uri;

class PdfInvoice extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    protected Mpdf    $mpdf;
    protected string  $watermark = '';
    protected bool    $rendered  = false;
    protected Invoice $invoice;


    public function __construct(Invoice $invoice, ?string $watermark = null)
    {
        $this->invoice = $invoice;

        if (is_null($watermark)) {
            switch ($this->invoice->status) {
                case Invoice::STATUS_OPEN:
                    $watermark = 'Open';
                    break;
                case Invoice::STATUS_PAID:
                    $watermark = 'Paid';
                    break;
                case Invoice::STATUS_CANCELLED:
                    $watermark = 'Cancelled';
                    break;
            }
        }
        $this->watermark = $watermark ?? '';

        $this->initPdf();
    }

    protected function initPdf(): void
    {
        $url = Uri::create()->toString();
        $html = $this->show()->toString();

        ini_set("memory_limit", "128M");

        $this->mpdf = new Mpdf([
            'margin_top' => 20,
        ]);

        $this->mpdf->setBasePath($url);

        $siteCompany = Factory::instance()->getOwnerCompany();
        $this->mpdf->SetTitle($siteCompany->name . ' - Invoice');
        $this->mpdf->SetAuthor($siteCompany->name);

        if ($this->watermark) {
            $this->mpdf->SetWatermarkText($this->watermark);
            $this->mpdf->showWatermarkText = true;
            $this->mpdf->watermark_font = 'DejaVuSansCondensed';
            $this->mpdf->watermarkTextAlpha = 0.1;
        }
        $this->mpdf->SetDisplayMode('fullpage');
        $this->mpdf->WriteHTML($html);
    }

    /**
     * Output the pdf to the browser
     */
    #[NoReturn] public function output(): void
    {
        $filename = 'Invoice-' . $this->invoice->invoiceId . '.pdf';
        $this->mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
        exit;
    }

    /**
     * Return the PDF as a string to attach to an email message
     */
    public function getPdfAttachment(string $filename = ''): string
    {
        if (!$filename) {
            $filename = 'Invoice-' . $this->invoice->invoiceId . '.pdf';
        }
        return $this->mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN);
    }

    /**
     * Execute the renderer.
     * Return an object that your framework can interpret and display.
     */
    public function show(): ?Template
    {
        $template = $this->getTemplate();

        if ($this->rendered) return $template;
        $this->rendered = true;

        $siteCompany = Factory::instance()->getOwnerCompany();

        $company = $this->invoice->getCompany();

        // Setup page
        $template->setTitleText('Invoice No: ' . $this->invoice->invoiceId);
        $template->setText('due-days', strval(Registry::instance()->get('account.due.days', Invoice::DEFAULT_DUE_DAYS)));

        $paymentText = Registry::instance()->get('site.invoice.payment', '');
        if ($paymentText) {
            $template->setHtml('invoice-payment', $paymentText);
            $template->setVisible('invoice-payment');
        }

        // Render Invoice
        $template->setText('invoice-id', $this->invoice->invoiceId);
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
            $issued = $this->invoice->issuedOn->format(\Tk\Date::FORMAT_SHORT_DATE);
            $due = $this->invoice->getDateDue()->format(\Tk\Date::FORMAT_SHORT_DATE);
        }
        $template->setText('dateIssued', $issued);
        $template->setText('date-due', $due);
        $template->setHtml('notes', $this->invoice->notes);

        if ($this->invoice->purchaseOrder) {
            $template->setText('purchaseOrder', $this->invoice->purchaseOrder);
            $template->setVisible('v-purchaseOrder');
        }


        // totals
        if (($this->invoice->discount) > 0 ||
            ($this->invoice->tax > 0) ||
            ($this->invoice->shipping->getAmount() > 0)
        ) {
            $template->setText('subTotal-amount', $this->invoice->subTotal);
            $template->setVisible('subTotal');
        }

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

        $template->setText('total-amount', $this->invoice->total);

        if ($this->invoice->paidTotal->getAmount() > 0) {
            $template->setText('paid-amount', '-'.$this->invoice->paidTotal);
            $template->setVisible('paid');
        }

        $template->setText('total', $this->invoice->total);

        $template->setText('status', $this->invoice->getStatus());
        $template->addCss('status', 'badge-' . Invoice::STATUS_CSS[$this->invoice->getStatus()]);

        $template->setText('payments', $this->invoice->paidTotal);
        if (!in_array($this->invoice->status, [\App\Db\Invoice::STATUS_OPEN, \App\Db\Invoice::STATUS_CANCELLED])) {
            $template->setText('outstanding', $this->invoice->unpaidTotal);
        } else {
            $template->setText('outstanding', '--');
        }

        // render item list
        foreach ($this->invoice->getItemList() as $item) {
            $row = $template->getRepeat('item');
            $row->setAttr('item', 'data-item-id', $item->invoiceItemId);
            $row->setText('itemId', $item->invoiceItemId);
            $row->setText('description', $item->description);
            $row->setText('qty', $item->qty);
            $row->setText('productCode', $item->productCode);
            $row->setText('price', $item->price);
            $row->setText('total', $item->total);
            $row->appendRepeat();
        }

        $pdfStyles = file_get_contents(Config::makePath('/src/App/Pdf/pdfStyles.css'));
        $template->appendCss($pdfStyles);

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

      <tr choice="subTotal">
        <td colspan="2"></td>
        <td class="text-end" colspan="2">Subtotal:</td>
        <td class="bak-grey money" var="subTotal-amount">0.00</td>
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
        <td class="text-end" colspan="2">Total:</td>
        <td class="bak-grey money" var="total">$0.00</td>
      </tr>
      <tr choice="paid">
        <td colspan="2"></td>
        <td class="text-end" colspan="2">Paid:</td>
        <td class="bak-grey money" var="paid-amount">-$0.00</td>
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
      <td class="box">
        Total<br/>
        <span var="total">$0.00</span>
      </td>
      <td class="sep">&nbsp;</td>
      <td class="box">
        Payments<br/>
        <span var="payments">$0.00</span>
      </td>
      <td class="sep">&nbsp;</td>
      <td class="box text-strong">
        Outstanding<br/>
        <span var="outstanding">$0.00</span>
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