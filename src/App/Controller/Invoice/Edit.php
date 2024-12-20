<?php
namespace App\Controller\Invoice;

use App\Component\InvoiceEditDialog;
use App\Component\ItemAddDialog;
use App\Component\StatusLogTable;
use App\Db\Company;
use App\Db\Invoice;
use App\Db\InvoiceItem;
use App\Db\User;
use App\Factory;
use Bs\Mvc\ControllerAdmin;
use Bs\Registry;
use Dom\Template;
use Tk\Alert;
use Tk\Date;
use Tk\Log;
use Tk\Uri;

class Edit extends ControllerAdmin
{
    protected ?Invoice           $invoice           = null;
    protected ?StatusLogTable    $statusLog         = null;
    protected ?ItemAddDialog     $itemAddDialog     = null;
    protected ?InvoiceEditDialog $invoiceEditDialog = null;


    public function doDefault(): void
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
            }
        }

        if (!$this->invoice) {
            Alert::addError('No invoice found');
            $this->getBackUrl()->redirect();
        }

        if ($_GET['act'] ?? false) {
            $this->doAction();
        }

        // Show status log component
        $this->statusLog = new StatusLogTable($this->invoice);
        if ($this->invoice->status == Invoice::STATUS_OPEN) {
            $this->itemAddDialog = new ItemAddDialog();
        }

        if (in_array($this->invoice->status, [Invoice::STATUS_OPEN, Invoice::STATUS_UNPAID])) {
            $this->invoiceEditDialog = new InvoiceEditDialog();
        }
    }

    public function doAction(): void
    {
        $action = trim($_GET['post'] ?? $_GET['act'] ?? '');
        switch ($action) {
            case 'del':
                $invoiceItemId = intval($_REQUEST['invoiceItemId'] ?? 0);
                $item = InvoiceItem::find($invoiceItemId);
                if ($item instanceof InvoiceItem) {
                    $item->delete();
                }
                break;
            case 'email':
                if (!\App\Email\Invoice::sendIssueInvoice($this->invoice)) {
                    Alert::addError("Failed to send invoice id {$this->invoice->invoiceId}");
                }
                break;
        }
        //Uri::create()->remove('act')->redirect();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->setVisible('btn-cancel', in_array($this->invoice->status, [Invoice::STATUS_OPEN, Invoice::STATUS_UNPAID]));
        $template->setVisible('btn-edit', in_array($this->invoice->status, [Invoice::STATUS_OPEN, Invoice::STATUS_UNPAID]));
        $template->setVisible('btn-pay', in_array($this->invoice->status, [Invoice::STATUS_UNPAID]));
        $template->setVisible('btn-issue', in_array($this->invoice->status, [Invoice::STATUS_OPEN]));
        $template->setVisible('btn-add-item', in_array($this->invoice->status, [Invoice::STATUS_OPEN]));

        // Status Log
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


        $this->showInvoice($template);

        if ($this->invoice->getStatus() == \App\Db\Invoice::STATUS_OPEN) {
            $js = <<<JS
jQuery(function ($) {

    // todo: review this script, could use HTMX instead???
  $('.tk-invoice').each(function () {
    var invoice = $(this);

    invoice.on('click', '.btn-delete', function (e) {
      var itemId = $(this).closest('tr').data('item-id');
      console.log(e);
      // if (confirm('Are you sure you want to remove this Item?')) {
      //   $.post(document.location, {act: 'del', itemId: itemId}, function (html) {
      //     invoice.find('.tk-invoice-box').replaceWith($(html).find('.tk-invoice .tk-invoice-box'));
      //   }, 'html');
      // }
    });

    invoice.on('change', '.input-qty', function (e) {
      var itemId = $(this).closest('tr').data('itemId');
        $.post(document.location, {act: 'qty', itemId: itemId, qty: $(this).val()}, function (html) {
          invoice.find('.tk-invoice-box').replaceWith($(html).find('.tk-invoice .tk-invoice-box'));
        }, 'html');
    });

  });

});
JS;
            $template->appendJs($js);
        }

        return $template;
    }

    public function showInvoice(Template $template): void
    {
        $company = $this->invoice->getCompany();
        if (!$company instanceof Company) {
            Log::warning("Only Company clients are supported at this time?");
            return;
        }

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
                $row->setAttr('qty-input', 'value', $item->qty);
                $row->setVisible('open');
            } else {
                $row->setText('qty', $item->qty);
            }
            $row->setText('price', $item->price);
            $row->setText('total', $item->getTotal());
            $row->appendRepeat();
        }

        // totals footer
        $template->setText('subTotal', $this->invoice->subTotal);

        if ($this->invoice->discount > 0) {
            $template->setText('discount-pcnt', round($this->invoice->discount * 100, 2) . '%');
            $template->setText('discount-amount', $this->invoice->getDiscountTotal());
            $template->setVisible('discount');
        }

        if ($this->invoice->tax > 0) {
            $template->setText('tax-pcnt', round($this->invoice->tax * 100, 2) . '%');
            $template->setText('tax-amount', $this->invoice->getTaxTotal());
            $template->setVisible('tax');
        }

        if ($this->invoice->shipping && $this->invoice->shipping->getAmount() > 0) {
            $template->setText('shipping', $this->invoice->shipping);
            $template->setVisible('shipping');
        }

        $template->setText('total', $this->invoice->total);
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="row tk-invoice">
    <div class="col-12">
        <div class="page-actions card mb-3">
            <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
            <div class="card-body" var="actions">
                <a href="/" title="Back" class="btn btn-outline-secondary me-1" var="back"><i class="fa fa-arrow-left"></i> Back</a>

                <a href="#" class="btn btn-light me-1" title="PDF" target="_blank" var="btn-pdf"><i class="fa fa-download"></i>
                    <span>PDF</span></a>
                <a href="#" class="btn btn-light me-1" title="Email" var="btn-email" data-confirm="Are you sure you want to email this invoice to the client?"><i class="fas fa-envelope"></i>
                    <span>Email</span></a>


                <a href="#" class="btn btn-danger float-end me-1" title="Cancel" choice="btn-cancel">
                    <i class="fas fa-bell-slash"></i>
                    <span>Cancel</span>
                </a>
                <a href="#" class="btn btn-primary float-end me-1" data-bs-toggle="modal" data-bs-target="" title="Add a new invoice item" choice="btn-edit">
                    <i class="fa fa-edit"></i>
                    <span>Edit</span>
                </a>
                <a href="#" class="btn btn-success float-end me-1" title="Add a new payment" choice="btn-pay">
                    <i class="fas fa-dollar-sign"></i>
                    <span>Add Payment</span>
                </a>
                <a href="#" class="btn btn-success float-end me-1" title="Issue Invoice" choice="btn-issue" data-confirm="Are you sure you want to issue this invoice?">
                    <i class="fas fa-bell"></i>
                    <span>Issue</span>
                </a>
                <a href="#" class="btn btn-warning float-end me-1" data-bs-toggle="modal" data-bs-target="" title="Add a new invoice item" choice="btn-add-item">
                    <i class="fa fa-plus-circle"></i>
                    <span>Add Item</span>
                </a>

            </div>
        </div>
    </div>

    <!-- Invoice Template -->
    <div class="col-8">
        <div class="card mb-3">
            <div class="card-header">
                <i class="far fa-credit-card"></i> Invoice #: <span var="invoiceId">00000</span>
            </div>
            <div class="card-body" var="content">
                <form role="form" class="tk-form">

                    <div class="row">
                        <div class="col-sm-8">
                            <h5>Billing Address</h5>
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
                                                <button type="button" class="btn btn-outline-danger btn-sm btn-delete" title="Remove Item"
                                                    data-confirm="Are you sure you want to remove this Item?"
                                                    hx-delete=""
                                                    hx-target=".tk-invoice-box"
                                                    hx-select=".tk-invoice-box"
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
                                                <input type="text" class="form-control input-qty" name="qty[]" value="5" var="qty-input" />
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
                                <small class="text-muted">
                                    All accounts are to be paid within
                                    <span var="due-days">7</span> days from receipt of
                                    invoice. If account is not paid within
                                    <span var="due-days">7</span> days late fees may be incurred.
                                </small>
                            </div>
                        </div> <!-- end col -->
                        <div class="col-sm-6 ps-2">
                            <div class="float-end pe-1">
                                <p>
                                    <b>Sub-total: </b>
                                    <span class="float-end ms-1" var="subTotal">$0.00</span>
                                </p>
                                <p choice="discount">
                                    <b>Discount (<span var="discount-pcnt"></span>) <a href="#" title="Edit Discount Amount" class="btn btn-default btn-sm" choice="ed-discount"><i class="fa fa-pencil"></i></a>: </b>
                                    <span class="float-end ms-1" var="discount-amount">$0.00</span>
                                </p>
                                <p choice="tax">
                                    <b>Tax (<span var="tax-pcnt"></span>) <a href="#" title="Edit Tax Amount" class="btn btn-default btn-sm" choice="ed-tax"><i class="fa fa-pencil"></i></a>: </b>
                                    <span class="float-end ms-1" var="tax-amount">$0.00</span>
                                </p>
                                <p choice="shipping">
                                    <b>Tax <a href="#" title="Edit Shipping Amount" class="btn btn-default btn-sm" choice="ed-shipping"><i class="fa fa-pencil"></i></a>: </b>
                                    <span class="float-end ms-1" var="shipping">$0.00</span>
                                </p>

                                <h3 var="total">$0.00</h3>
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