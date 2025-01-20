<?php
namespace App\Controller\Invoice;

use App\Component\InvoiceEditDialog;
use App\Component\ItemAddDialog;
use App\Component\PaymentAddDialog;
use App\Component\PaymentTable;
use App\Component\StatusLogTable;
use App\Db\Company;
use App\Db\Invoice;
use App\Db\InvoiceItem;
use App\Db\StatusLog;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Registry;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Alert;
use Tk\Date;
use Tk\Log;
use Tk\Uri;

class Edit extends ControllerAdmin
{
    protected ?Invoice           $invoice           = null;
    protected ?StatusLogTable    $statusLog         = null;
    protected ?PaymentTable      $paymentTable      = null;
    protected ?ItemAddDialog     $itemAddDialog     = null;
    protected ?InvoiceEditDialog $invoiceEditDialog = null;
    protected ?PaymentAddDialog  $paymentAddDialog  = null;


    public function doDefault(): mixed
    {
        $this->getPage()->setTitle('Edit Invoice');

        if (!User::getAuthUser()?->isStaff()) {
            Alert::addWarning('You do not have permission to access this page');
            User::getAuthUser()?->getHomeUrl()->redirect() ?? Uri::create('/')->redirect();
        }

        $invoiceId = intval($_GET['invoiceId'] ?? 0);
        $companyId = intval($_GET['companyId'] ?? 0);

        if ($invoiceId) {
            $this->invoice = Invoice::find($invoiceId);
        } elseif ($companyId) {
            $company = Company::find($companyId);
            if ($company instanceof Company) {
                $this->invoice = Invoice::getOpenInvoice($company);
                Breadcrumbs::popCrumb();
                Uri::create()->reset()->set('invoiceId', $this->invoice->invoiceId)->redirect();
            }
        }

        if (!$this->invoice) {
            Alert::addError('No invoice found');
            $this->getBackUrl()->redirect();
        }

        if ($_GET['act'] ?? false) {
            return $this->doAction();
        }

        // Show status log component
        $this->statusLog = new StatusLogTable($this->invoice);
        if ($this->invoice->status == Invoice::STATUS_OPEN) {
            $this->itemAddDialog = new ItemAddDialog();
        }

        $this->invoiceEditDialog = new InvoiceEditDialog();

        if ($this->invoice->status == Invoice::STATUS_UNPAID) {
            $this->paymentAddDialog = new PaymentAddDialog();
        }
        if ($this->invoice->paidTotal->getAmount() > 0) {
            $this->paymentTable = new PaymentTable();
        }

        return null;
    }

    public function doAction(): mixed
    {
        $action = trim($_GET['post'] ?? $_GET['act'] ?? '');
        switch ($action) {
            case 'pdf':
                $ren = new \App\Pdf\PdfInvoice($this->invoice);
                $ren->output();
                //return $ren->show();  // to show HTML
                break;
            case 'issue':
                $this->invoice->doIssue();
                Alert::addSuccess("Invoiced issued to client");
                Uri::create()->remove('act')->redirect();
                break;
            case 'qty':
                $invoiceItemId = intval($_REQUEST['invoiceItemId'] ?? 0);
                $item = InvoiceItem::find($invoiceItemId);
                if ($item instanceof InvoiceItem) {
                    $item->qty = intval($_POST['qty'][$invoiceItemId] ?? 1);
                    $item->save();
                }
                break;
            case 'del':
                $invoiceItemId = intval($_REQUEST['invoiceItemId'] ?? 0);
                $item = InvoiceItem::find($invoiceItemId);
                if ($item instanceof InvoiceItem) {
                    $item->delete();
                }
                break;
            case 'cancel':
                if ($this->invoice instanceof Invoice) {
                    $this->invoice->doCancel();
                    StatusLog::create($this->invoice, "Invoice cancelled");
                    Alert::addSuccess("Invoice #{$this->invoice->invoiceId} cancelled");
                    Uri::create()->remove('act')->redirect();
                }
                break;
            case 'email':
                if (!\App\Email\Invoice::sendIssueInvoice($this->invoice)) {
                    Alert::addError("Failed to send invoice id {$this->invoice->invoiceId}");
                }
                Uri::create()->remove('act')->redirect();
                break;
        }

        return null;
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('back', 'href', $this->getBackUrl());

        //$template->setVisible('btn-edit', in_array($this->invoice->status, [Invoice::STATUS_OPEN, Invoice::STATUS_UNPAID, Invoice::STATUS_CANCELLED]));
        $template->setVisible('btn-edit', true);
        $template->setVisible('btn-cancel', in_array($this->invoice->status, [Invoice::STATUS_OPEN, Invoice::STATUS_UNPAID]));
        $template->setVisible('btn-pay', in_array($this->invoice->status, [Invoice::STATUS_UNPAID]));
        $template->setVisible('btn-issue', in_array($this->invoice->status, [Invoice::STATUS_OPEN]));
        $template->setVisible('btn-add-item', in_array($this->invoice->status, [Invoice::STATUS_OPEN]));

        $cancel = Uri::create()->set('act', 'cancel');
        $template->setAttr('btn-cancel', 'href', $cancel);

        $email = Uri::create()->set('act', 'email');
        $template->setAttr('btn-email', 'href', $email);

        $issue = Uri::create()->set('act', 'issue');
        $template->setAttr('btn-issue', 'href', $issue);

        $pdf = Uri::create()->set('act', 'pdf');
        $template->setAttr('btn-pdf', 'href', $pdf);

        if ($this->paymentTable) {
            $html = $this->paymentTable->doDefault();
            $template->appendHtml('components', $html);
        }

        if ($this->statusLog) {
            $html = $this->statusLog->doDefault();
            $template->appendHtml('components', $html);
        }

        if ($this->itemAddDialog) {
            $template->setAttr('btn-add-item', 'data-bs-target', "#{$this->itemAddDialog->getDialogId()}");
            $tpl = $this->itemAddDialog->doDefault();
            if ($tpl) $template->appendTemplate('dialogs', $tpl);
        }

        if ($this->invoiceEditDialog) {
            $template->setAttr('btn-edit', 'data-bs-target', "#{$this->invoiceEditDialog->getDialogId()}");
            $tpl = $this->invoiceEditDialog->doDefault();
            if ($tpl) $template->appendTemplate('dialogs', $tpl);
        }

        if ($this->paymentAddDialog) {
            $template->setAttr('btn-pay', 'data-bs-target', "#{$this->paymentAddDialog->getDialogId()}");
            $tpl = $this->paymentAddDialog->doDefault();
            if ($tpl) $template->appendTemplate('dialogs', $tpl);
        }

        $this->showInvoice($template);

        return $template;
    }

    public function showInvoice(Template $template): void
    {
        $company = $this->invoice->getCompany();
        if (!$company instanceof Company) {
            Log::warning("Only Company clients are supported at this time?");
            return;
        }

        $this->invoice->reload();

        $template->setText('invoiceId', $this->invoice->getId());
        $template->setText('due-days', strval(Registry::instance()->get('account.due.days', Invoice::DEFAULT_DUE_DAYS)));
        $template->setText('clientName', $company->name);
        $template->setAttr('clientName', 'href', Uri::create('/companyEdit', ['companyId' => $company->companyId]));

        $html = sprintf('<span class="badge bg-%s">%s</span>',
            Invoice::STATUS_CSS[$this->invoice->status],
            e(Invoice::STATUS_LIST[$this->invoice->status])
        );
        $template->setHtml('status', $html);

        if ($company->contact) {
            $template->setText('clientContact', $company->contact);
            $template->setVisible('clientContact');
        }

        if ($company->address) {
            $template->setText('address', $company->address);
            $template->setVisible('v-address');
        }

        if ($this->invoice->issuedOn) {
            $template->setText('issuedOn', $this->invoice->issuedOn->format(Date::FORMAT_MED_DATE));
            $template->setVisible('v-issuedOn');
        }

        if ($this->invoice->getDateDue()) {
            $template->setText('issuedOn', $this->invoice->getDateDue()->format(Date::FORMAT_MED_DATE));
            $template->setVisible('v-dueOn');
        }

        if ($this->invoice->purchaseOrder) {
            $template->setText('purchaseOrder', $this->invoice->purchaseOrder);
            $template->setVisible('v-purchaseOrder');
        }

        if ($company->phone) {
            $template->setText('phone', $company->phone);
            $template->setAttr('phone', 'href', 'tel:'.preg_replace('/[^0-9]/', '', $company->phone));
            $template->setVisible('v-phone');
        }

        if ($company->email) {
            $template->setText('email', $company->email);
            $template->setAttr('email', 'href', 'mailto:'.$company->email);
            $template->setVisible('v-email');
        }

        if ($company->accountsEmail) {
            $template->setText('accountsEmail', $company->accountsEmail);
            $template->setAttr('accountsEmail', 'href', 'mailto:'.$company->accountsEmail);
            $template->setVisible('v-accountsEmail');
        }

        if ($this->invoice->billingAddress) {
            $template->setHtml('billingAddress', $this->invoice->billingAddress);
            $template->setVisible('v-billingAddress');
        }

        if ($this->invoice->status == \App\Db\Invoice::STATUS_OPEN) {
            $template->setVisible('open');
        }

        if ($this->invoice->notes) {
            $template->setHtml('notes', $this->invoice->notes);
            $template->setVisible('notes');
        }

        // render item list
        foreach ($this->invoice->getItemList() as $item) {
            $row = $template->getRepeat('item');
            $row->setAttr('item', 'data-item-id', $item->invoiceItemId);
            $row->setText('itemId', $item->invoiceItemId);

            $del = Uri::create()->set('invoiceItemId', $item->invoiceItemId)->set('act', 'del');
            $row->setAttr('delete', 'hx-delete', $del);

            $model = $item->getModel();
            if ($model instanceof \App\Db\Task) {
                $url = Uri::create('/taskEdit')->set('taskId', $model->getId());
                $row->setHtml('productCode', sprintf('<a href="%s" title="Edit/View Task">%s</a>', $url->toString(), e($item->productCode)));
            } else if ($model instanceof \App\Db\Product) {
                $url = Uri::create('/productEdit')->set('productId', $model->getId());
                $row->setHtml('productCode', sprintf('<a href="%s" title="Edit/View Product">%s</a>', $url->toString(), e($item->productCode)));
            } else {
                $row->setText('productCode', e($item->productCode));
            }

            $row->setText('description', $item->description);
            if ($this->invoice->status == \App\Db\Invoice::STATUS_OPEN) {
                $qty = Uri::create()->set('invoiceItemId', $item->invoiceItemId)->set('act', 'qty');
                $row->setAttr('qty-input', 'hx-post', $qty);
                $row->setAttr('qty-input', 'name', "qty[{$item->invoiceItemId}]");

                $row->setAttr('qty-input', 'value', $item->qty);
                $row->setVisible('open');
            } else {
                $row->setText('qty', $item->qty);
            }
            $row->setText('price', $item->price);
            $row->setText('total', $item->total);
            $row->appendRepeat();
        }

        // totals footer
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

        if ($this->invoice->status == Invoice::STATUS_UNPAID) {
            $template->setText('outstanding-amount', $this->invoice->unpaidTotal);
            $template->setVisible('outstanding');
        }

    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="row tk-invoice">
    <div class="col-12">
        <div class="page-actions card mb-3">
            <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
            <div class="card-body" var="actions">
                <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>

                <a href="#" class="btn btn-outline-secondary" title="PDF" target="_blank" var="btn-pdf">
                    <i class="fa fa-download"></i>
                    <span>PDF</span>
                </a>
                <a href="#" class="btn btn-outline-secondary" title="Email" var="btn-email" data-confirm="Are you sure you want to email this invoice to the client?">
                    <i class="fas fa-envelope"></i>
                    <span>Email</span>
                </a>

                <a href="#" class="btn btn-danger float-end" title="Cancel" choice="btn-cancel" data-confirm="Are you sure you want to cancel this invoice?">
                    <i class="fas fa-bell-slash"></i>
                    <span>Cancel</span>
                </a>
                <a href="#" class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="" title="Add a new invoice item" choice="btn-edit">
                    <i class="fa fa-edit"></i>
                    <span>Edit</span>
                </a>
                <a href="#" class="btn btn-success float-end" data-bs-toggle="modal" title="Add a new payment" choice="btn-pay">
                    <i class="fas fa-dollar-sign"></i>
                    <span>Add Payment</span>
                </a>
                <a href="#" class="btn btn-success float-end" title="Issue Invoice" choice="btn-issue" data-confirm="Are you sure you want to issue this invoice?">
                    <i class="fas fa-bell"></i>
                    <span>Issue</span>
                </a>
                <a href="#" class="btn btn-warning float-end" data-bs-toggle="modal" data-bs-target="" title="Add a new invoice item" choice="btn-add-item">
                    <i class="fa fa-plus-circle"></i>
                    <span>Add Item</span>
                </a>

            </div>
        </div>
    </div>

    <!-- Invoice Template -->
    <div class="col-8" id="tk-invoice-container">
        <div class="card mb-3">
            <div class="card-header">
                <i class="far fa-credit-card"></i> Invoice #: <span var="invoiceId">00000</span>
            </div>
            <div class="card-body" var="content">
                <form role="form" class="tk-form">

                    <div class="row">
                        <div class="col-sm-8">
                            <h5>Client</h5>
                            <address>
                                <strong><a href="#" var="clientName"></a></strong><br>
                                <strong choice="clientContact"></strong><br>
                                <span choice="v-billingAddress"><span var="billingAddress"></span><br></span>
                                <span choice="v-phone"><abbr title="Phone">PH:</abbr> <a href="tel:0000000" var="phone"></a><br></span>
                                <span choice="v-email"><abbr title="Email">Email:</abbr> <a href="mailto:" var="email"></a><br></span>
                                <span choice="v-accountsEmail"><abbr title="Accounts Email">Accounts Email:</abbr> <a href="mailto:" var="accountsEmail"></a><br></span>
                                <span choice="v-address"><abbr title="Address">Address:</abbr> <span var="address"></span><br></span>
                            </address>
                        </div> <!-- end col -->

                        <div class="col-sm-4">
                            <div class="mt-3 mb-3">
                                <span class=""><strong>Invoice # : </strong> <span class="float-end" var="invoiceId">0000</span><br></span>
                                <span class=""><strong>Invoice Status : </strong> <span class="float-end" var="status"><span class="badge bg-danger">Unpaid</span></span><br></span>
                                <span class="" choice="v-issuedOn"><strong>Invoiced Date : </strong> <span class="float-end" var="issuedOn">Jan 17, 2019</span><br></span>
                                <span class="" choice="v-dueOn"><strong>Due Date : </strong> <span class="float-end" var="dueOn">Jan 17, 2019</span><br></span>
                                <span class="" choice="v-purchaseOrder"><strong>Purchase Order # : </strong> <span class="float-end" var="purchaseOrder">0000</span><br></span>
                            </div>
                        </div> <!-- end col -->
                    </div>

                    <div class="row tk-invoice-box">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="tk-table table mt-2 table-centered">
                                    <thead>
                                        <tr>
                                            <th choice="open">Actions</th>
                                            <th class="text-center" title="Product Code">#</th>
                                            <th class="max-width">Item</th>
                                            <th style="min-width: 70px;">Qty</th>
                                            <th>Unit $</th>
                                            <th class="text-end">Total $</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr data-item-id="0" repeat="item">
                                            <td choice="open">
                                                <button type="button" class="btn btn-outline-danger btn-sm btn-delete" title="Delete Item"
                                                    hx-delete=""
                                                    hx-target="#tk-invoice-container"
                                                    hx-select="#tk-invoice-container"
                                                    hx-swap="outerHTML"
                                                    hx-confirm="Delete the selected invoice item?"
                                                    var="delete">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                            <td var="productCode">1</td>
                                            <td var="description">
                                                <b>Web Design</b> <br />
                                                2 Pages static website - my website
                                            </td>
                                            <td class="text-center" var="qty">
                                                <input type="text" class="form-control" name="qty[]" value="0"
                                                    hx-post=""
                                                    hx-trigger="keyup changed delay:500ms"
                                                    hx-target="#tk-invoice-container"
                                                    hx-select="#tk-invoice-container"
                                                    hx-swap="outerHTML"
                                                    var="qty-input"/>
                                            </td>
                                            <td class="text-end" var="price">$0.00</td>
                                            <td class="text-end" var="total">$0.00</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div> <!-- end table-responsive -->
                        </div> <!-- end col -->
                    </div>
                    <!-- end row -->

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <div class="clearfix">
                                <h6 class="text-muted">Notes:</h6>
                                <div choice="notes"></div>
                                <p>
                                    <small class="text-muted">
                                        All accounts are to be paid within
                                        <span var="due-days">7</span> days from receipt of
                                        invoice.<br> Late payments may incur a 10% fee.
                                    </small>
                                </p>
                            </div>
                        </div> <!-- end col -->
                        <div class="col-sm-6 ps-2">
                            <div class="float-end pe-1">
                                <p choice="subTotal">
                                    <b>Sub-total: </b>
                                    <span class="float-end ms-1" var="subTotal-amount">$0.00</span>
                                </p>
                                <p choice="discount">
                                    <b>Discount (<span var="discount-pcnt"></span>): </b>
                                    <span class="float-end ms-1" var="discount-amount">$0.00</span>
                                </p>
                                <p choice="tax">
                                    <b>Tax (<span var="tax-pcnt"></span>): </b>
                                    <span class="float-end ms-1" var="tax-amount">$0.00</span>
                                </p>
                                <p choice="shipping">
                                    <b>Shipping: </b>
                                    <span class="float-end ms-1" var="shipping-amount">$0.00</span>
                                </p>
                                <p>
                                    <b>Total: </b>
                                    <span class="float-end ms-1" var="total-amount">$0.00</span>
                                </p>
                                <p choice="paid">
                                    <b>Paid: </b>
                                    <span class="float-end ms-1" var="paid-amount">-$0.00</span>
                                </p>

                                <h3 choice="outstanding"><span var="outstanding-amount">$0.00</span></h3>
                            </div>
                            <div class="clearfix"></div>
                        </div> <!-- end col -->
                    </div>
                    <!-- end row -->

                </form>
            </div>
        </div>
    </div>
    <!-- END: Invoice Template -->

    <div class="col-4" var="components"></div>

<!--    <div hx-get="/component/addItemDialog" hx-trigger="load" hx-swap="outerHTML" var="addItemDialog"></div>-->
    <div class="dialog-container" var="dialogs"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}