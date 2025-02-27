<?php
namespace App\Component;

use App\Db\Invoice;
use App\Db\InvoiceItem;
use App\Db\Payment;
use App\Db\Product;
use App\Db\User;
use App\Form\Field\Datalist;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Collection;
use Tk\Db;
use Tk\Form\Action\Link;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Input;
use Tk\Form\Field\InputGroup;
use Tk\Form\Field\Select;
use Tk\Form\Field\Textarea;
use Tk\Log;
use Tk\Uri;

class PaymentAddDialog extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    const string CONTAINER_ID = 'invoice-add-payment-dialog';

    protected ?Form        $form     = null;
    protected array        $hxEvents = [];
    protected ?Invoice     $invoice  = null;
    protected ?Payment     $payment  = null;


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $invoiceId = (int)($_POST['invoiceId'] ?? $_GET['invoiceId'] ?? 0);
        $this->invoice = Invoice::find($invoiceId);
        if (!($this->invoice instanceof Invoice)) {
            Log::error("invalid invoice ID {$invoiceId}");
            return null;
        }

        $this->payment = new Payment();

        $this->form = new Form($this->payment);
        $this->form->setAction('');
        $this->form->setAttr('hx-post', Uri::create('/component/paymentAddDialog', ['invoiceId' => $this->invoice->invoiceId]));
        $this->form->setAttr('hx-swap', 'outerHTML');
        $this->form->setAttr('hx-target', "#{$this->form->getId()}");
        $this->form->setAttr('hx-select', "#{$this->form->getId()}");

        $this->form->appendField((new Select('method', Payment::METHOD_LIST))
            ->prependOption('-- Select --', ''));

        $this->form->appendField(new InputGroup('amount', '$'))->setLabel('Payment Amount');

        $this->form->appendField(new Textarea('notes'));

        $this->form->appendField(new Link('cancel', Uri::create('#')))
            ->setAttr('data-bs-dismiss', 'modal')
            ->addCss('float-end');
        $this->form->appendField(new Submit('save', [$this, 'onSubmit']))
            ->addCss('float-end');

        $load = $this->form->unmapModel($this->payment);
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

        // Send HX event headers
        if (count($this->hxEvents)) {
            header(sprintf('HX-Trigger: %s', json_encode($this->hxEvents)));
        }

        return $this->show();
    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $form->mapModel($this->payment);

        // Check that the invoice is in the unpaid status.
        if ($this->invoice->getStatus() != \App\Db\Invoice::STATUS_UNPAID) {
            $form->addFieldError('method', 'You can only add payments to invoices with a status of `Unpaid`.');
        }

        // Check the payment amount does not exceed the invoice remainder amount (can be less)
        if ($this->payment->amount->greaterThan($this->invoice->unpaidTotal)) {
            $form->addFieldError('price', 'The payment amount cannot exceed '.$this->invoice->unpaidTotal->toString().'.');
        }

        $form->addFieldErrors($this->payment->validate());
        if ($form->hasErrors()) {
            $this->hxEvents['tkForm:onError'] = ['status' => 'err', 'errors' => $form->getAllErrors()];
            return;
        }

        $this->invoice->addPayment($this->payment);

        // Trigger HX events
        $this->hxEvents['tkForm:afterSubmit'] = ['status' => 'ok'];
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('dialog', 'id', $this->getDialogId());

        $this->form->getRenderer()->getTemplate()->addCss('actions', 'mt-4 float-end');
        $this->form->getRenderer()->getTemplate()->removeCss('fields', 'g-3 mt-1')->addCss('fields', 'g-2');

        $template->appendTemplate('content', $this->form->show());

        return $template;
    }

    public function getDialogId(): string
    {
        return self::CONTAINER_ID;
    }

    public function __makeTemplate(): ?Template
    {
        $unpaidTotal = $this->invoice->unpaidTotal->toFloatString();

        $html = <<<HTML
<div class="modal fade" data-bs-backdrop="static" var="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Add Payment</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" var="content"></div>
    </div>
  </div>
<script>
  jQuery(function($) {
    const dialog = '#{$this->getDialogId()}';
    const form   = '#{$this->form->getId()}';
    const unpaid = '{$unpaidTotal}';

    // reload page after successfull submit
    $(document).on('tkForm:afterSubmit', function(e) {
        if (!$(e.detail.elt).is(form)) return;
        $(dialog).modal('hide');
    });

    // reset form fields
    $(dialog).on('show.bs.modal', function(e) {
        $('[name=method]', this).val('eft');
        $('[name=amount]', this).val(unpaid);
        $('[name=notes]', this).val('');
        $('.is-invalid', this).removeClass('is-invalid');
    });

});
</script>
</div>
HTML;
        return Template::load($html);
    }

}
