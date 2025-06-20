<?php
namespace App\Controller\Invoice;

use App\Db\Company;
use App\Db\Invoice;
use App\Db\InvoiceItem;
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
    protected ?Invoice $invoice = null;


    public function doDefault(): mixed
    {
        $this->getPage()->setTitle('Edit Invoice', 'far fa-credit-card');
        $this->validateAccess(User::getAuthUser()?->isStaff() ?? false);

        $invoiceId = intval($_REQUEST['invoiceId'] ?? 0);
        $companyId = intval($_REQUEST['companyId'] ?? 0);

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
            Breadcrumbs::getBackUrl()->redirect();
        }

        if ($_GET['act'] ?? false) {
            return $this->doAction();
        }

        return null;
    }

    public function doAction(): mixed
    {
        $action = trim($_REQUEST['act'] ?? '');
        switch ($action) {
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
            case 'open':
                $this->invoice->reopen();
                Uri::create()->remove('act')->redirect();
                break;
        }

        return null;
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->addCss('icon', $this->getPage()->getIcon());

        if ($this->invoice->invoiceId) {
            $template->setVisible('edit');
            $template->setText('modified', $this->invoice->modified->format(Date::FORMAT_LONG_DATETIME));
            $template->setText('created', $this->invoice->created->format(Date::FORMAT_LONG_DATETIME));
        }

        //$template->setVisible('btn-edit', in_array($this->invoice->status, [Invoice::STATUS_OPEN, Invoice::STATUS_UNPAID, Invoice::STATUS_CANCELLED]));
        $template->setVisible('btn-edit', true);
        $template->setVisible('btn-cancel', in_array($this->invoice->status, [Invoice::STATUS_OPEN, Invoice::STATUS_UNPAID]));
        $template->setVisible('btn-pay', $this->invoice->status == Invoice::STATUS_UNPAID);
        $template->setVisible('btn-issue', $this->invoice->status == Invoice::STATUS_OPEN);
        $template->setVisible('btn-add-item', $this->invoice->status == Invoice::STATUS_OPEN);

        $cancel = Uri::create()->set('act', 'cancel');
        $template->setAttr('btn-cancel', 'href', $cancel);

        $email = Uri::create()->set('act', 'email');
        $template->setAttr('btn-email', 'href', $email);

        $issue = Uri::create()->set('act', 'issue');
        $template->setAttr('btn-issue', 'href', $issue);
        if ($this->invoice->total->getAmount() == 0) {
            $template->addCss('btn-issue', 'disabled');
        }

        $pdf = Uri::create('/pdf/invoice')->set('invoiceId', $this->invoice->invoiceId);
        $template->setAttr('btn-pdf', 'href', $pdf);

        $pdf = Uri::create('/pdf/taskList')->set('invoiceId', $this->invoice->invoiceId);
        $template->setAttr('btn-pdf-task', 'href', $pdf);

        if (in_array($this->invoice->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PAID])) {
            $url = Uri::create('/component/paymentTable', ['invoiceId' => $this->invoice->invoiceId]);
            $template->setAttr('paymentsTable', 'hx-get', $url);
            $template->setVisible('paymentsTable');
            $template->setVisible('components');
        }

        if (count($this->invoice->getOutstanding())) {
            $url = Uri::create('/component/invoiceOutstandingTable', ['invoiceId' => $this->invoice->invoiceId]);
            $template->setAttr('outstandingTable', 'hx-get', $url);
            $template->setVisible('outstandingTable');
        }

        $url = Uri::create('/component/invoiceEditDialog', ['invoiceId' => $this->invoice->invoiceId]);
        $template->setAttr('btn-edit', 'hx-get', $url);

        if ($this->invoice->status == Invoice::STATUS_OPEN) {
            $url = Uri::create('/component/itemEditDialog', ['invoiceId' => $this->invoice->invoiceId]);
            $template->setAttr('btn-add-item', 'hx-get', $url);
            $template->setVisible('itemAddDialog');
        }

        if ($this->invoice->status == Invoice::STATUS_UNPAID) {
            $url = Uri::create('/component/paymentAddDialog', ['invoiceId' => $this->invoice->invoiceId]);
            $template->setAttr('btn-pay', 'hx-get', $url);
            $template->setVisible('paymentAddDialog');
        }

        if (in_array($this->invoice->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_CANCELLED])) {
            $url = Uri::create()->set('act', 'open');
            $template->setAttr('btn-open', 'href', $url);
            $template->setVisible('btn-open');
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
        $template->setText('due-days', strval(Registry::getValue('account.due.days', Invoice::DEFAULT_OVERDUE_DAYS)));
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
            $template->setText('issuedOn', $this->invoice->issuedOn->format(Date::FORMAT_AU_DATE));
            $template->setVisible('v-issuedOn');
        }

        if ($this->invoice->getDateDue()) {
            $template->setText('issuedOn', $this->invoice->getDateDue()->format(Date::FORMAT_AU_DATE));
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

        if ($this->invoice->status == Invoice::STATUS_UNPAID) {
            $outstanding = $this->invoice->getOutstandingAmount();
            if ($outstanding->getAmount() > 0) {
                $template->setText('outstanding-amount', $outstanding->toString());
                $template->setVisible('outstanding');
            }

            $payable = $outstanding->add($this->invoice->unpaidTotal);
            $template->setText('total-payable-amount', $payable);
            $template->setVisible('total-payable');
        }

    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="row tk-invoice">
    <div class="col-md-12">
        <div class="page-actions card mb-3">
            <div class="card-body" var="actions">
                <a href="#" class="btn btn-outline-secondary" title="PDF" target="_blank" var="btn-pdf">
                    <i class="fa fa-download"></i>
                    <span>PDF</span>
                </a>
                <a href="#" class="btn btn-outline-secondary" title="PDF" target="_blank" var="btn-pdf-task">
                    <i class="fa fa-download"></i>
                    <span>Task List</span>
                </a>
                <a href="#" class="btn btn-outline-secondary" title="Email" data-confirm="Are you sure you want to email this invoice to the client?" var="btn-email">
                    <i class="fas fa-envelope"></i>
                    <span>Email</span>
                </a>

                <a href="#" class="btn btn-warning float-end" title="Re-Open" data-confirm="Are you sure you want to re-open this invoice?" choice="btn-open">
                    <i class="far fa-credit-card me-1"></i>
                    <span>Re-Open</span>
                </a>

                <a href="#" class="btn btn-danger float-end" title="Cancel" data-confirm="Are you sure you want to cancel this invoice?" choice="btn-cancel">
                    <i class="fas fa-bell-slash"></i>
                    <span>Cancel</span>
                </a>

                <a href="#" class="btn btn-primary float-end" title="Add a new invoice item" choice="btn-edit"
                    hx-get="/component/invoiceEditDialog"
                    hx-trigger="click queue:none"
                    hx-target="body"
                    hx-swap="beforeend">
                    <i class="fa fa-edit"></i>
                    <span>Edit</span>
                </a>

                <a href="#" class="btn btn-success float-end" title="Add a new payment" choice="btn-pay"
                    hx-get="/component/paymentAddDialog"
                    hx-trigger="click queue:none"
                    hx-target="body"
                    hx-swap="beforeend">
                    <i class="fas fa-dollar-sign"></i>
                    <span>Add Payment</span>
                </a>

                <a href="#" class="btn btn-success float-end" title="Issue Invoice" data-confirm="Are you sure you want to issue this invoice?" choice="btn-issue">
                    <i class="fas fa-bell"></i>
                    <span>Issue</span>
                </a>

                <a href="#" class="btn btn-warning float-end" title="Add a new invoice item" choice="btn-add-item"
                    hx-get=""
                    hx-trigger="click queue:none"
                    hx-target="body"
                    hx-swap="beforeend">
                    <i class="fa fa-plus-circle"></i>
                    <span>Add Item</span>
                </a>

            </div>
        </div>
    </div>

    <!-- Invoice Template -->
    <div class="col" id="tk-invoice-container">
        <div class="card mb-3">
            <div class="card-header">
              <div class="info-dropdown dropdown float-end" title="Details" choice="edit">
                <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
                <div class="dropdown-menu dropdown-menu-end">
                  <p class="dropdown-item"><span class="d-inline-block">Modified:</span> <span var="modified">...</span></p>
                  <p class="dropdown-item"><span class="d-inline-block">Created:</span> <span var="created">...</span></p>
                </div>
              </div>
              <i var="icon"></i> Invoice #: <span var="invoiceId">00000</span>
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
                            <table class="table table-borderless totals">
                                <tr choice="discount">
                                    <td>Discount (<span var="discount-pcnt"></span>):</td>
                                    <td var="discount-amount">$0.00</td>
                                </tr>
                                <tr choice="tax">
                                    <td>Tax (<span var="tax-pcnt"></span>):</td>
                                    <td var="tax-amount">$0.00</td>
                                </tr>
                                <tr choice="shipping">
                                    <td>Shipping:</td>
                                    <td var="shipping-amount">$0.00</td>
                                </tr>
                                <tr choice="shipping">
                                    <td>Shipping:</td>
                                    <td var="shipping-amount">$0.00</td>
                                </tr>
                                <tr>
                                    <td>Sub-total:</td>
                                    <td var="subTotal-amount">$0.00</td>
                                </tr>
                                <tr choice="paid">
                                    <td>Paid:</td>
                                    <td var="paid-amount">$0.00</td>
                                </tr>
                                <tr>
                                    <td>Total:</td>
                                    <td class="text-strong" var="total-amount">$0.00</td>
                                </tr>
                                <tr choice="outstanding">
                                    <td>Outstanding Invoices:</td>
                                    <td var="outstanding-amount">$0.00</td>
                                </tr>
                                <tr choice="total-payable">
                                    <td>Total Payable:</td>
                                    <td var="total-payable-amount">$0.00</td>
                                </tr>
                            </table>
                        </div> <!-- end col -->
                    </div>
                    <!-- end row -->

                </form>
            </div>
        </div>
    </div>
    <!-- END: Invoice Template -->

    <div class="col-md-4" choice="components">
        <div hx-get="/component/invoiceOutstandingTable" hx-trigger="load" hx-swap="outerHTML" choice="outstandingTable">
          <p class="text-center mt-4"><i class="fa fa-fw fa-spin fa-spinner fa-3x"></i><br>Loading...</p>
        </div>
        <div hx-get="/component/paymentTable" hx-trigger="load" hx-swap="outerHTML" choice="paymentsTable">
          <p class="text-center mt-4"><i class="fa fa-fw fa-spin fa-spinner fa-3x"></i><br>Loading...</p>
        </div>

    </div>

<script>
    jQuery(function ($) {
        $(document).on('tkForm:afterSubmit', function(e) {
            location = location.href;
        });
    });
</script>

<style>
table.totals td:first-child {
    font-weight: bold;
    text-align: right;
    width: 100%;
}
table.totals tr:last-child{
    font-weight: bold;
    text-align: right;
    font-size: 2em;
}
table.totals td:last-child {
    text-align: right;
    white-space: nowrap;
}
</style>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}