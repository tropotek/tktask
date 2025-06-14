<?php
namespace App\Component;

use App\Db\Invoice;
use App\Db\Payment;
use App\Db\User;
use Bs\Mvc\ComponentInterface;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Form\Action\Link;
use Tk\Form\Action\Submit;
use Tk\Form\Field\InputGroup;
use Tk\Form\Field\Select;
use Tk\Form\Field\Textarea;
use Tk\Log;
use Tk\Uri;

class PaymentAddDialog extends \Dom\Renderer\Renderer implements ComponentInterface
{
    const string CONTAINER_ID = 'invoice-add-payment-dialog';

    protected ?Form        $form       = null;
    protected ?Invoice     $invoice    = null;
    protected ?Payment     $payment    = null;
    protected array        $hxTriggers = [];


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $invoiceId = (int)($_REQUEST['invoiceId'] ?? 0);
        $this->invoice = Invoice::find($invoiceId);
        if (!($this->invoice instanceof Invoice)) {
            Log::error("invalid invoice ID {$invoiceId}");
            return null;
        }

        $this->payment = new Payment();

        $this->form = new Form($this->payment, 'form-payment-add');
        $this->form->setAction('');
        $this->form->setAttr('hx-post', Uri::create('/component/paymentAddDialog', ['invoiceId' => $this->invoice->invoiceId]));
        $this->form->setAttr('hx-swap', 'outerHTML');
        $this->form->setAttr('hx-target', "#{$this->form->getId()}");
        $this->form->setAttr('hx-select', "#{$this->form->getId()}");

        $this->form->appendField((new Select('method', Payment::METHOD_LIST))
            ->prependOption('-- Select --', ''))
            ->setRequired();

        $this->form->appendField(new InputGroup('amount', '$'))
            ->setLabel('Payment Amount')
            ->setRequired();

        $this->form->appendField(new Textarea('notes'));

        $this->form->appendField(new Link('cancel', Uri::create('#')))
            ->setAttr('data-bs-dismiss', 'modal')
            ->addCss('float-end');
        $this->form->appendField(new Submit('save', [$this, 'onSubmit']))
            ->addCss('float-end');

        $load = $this->payment->unmapForm();
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

        if (!$this->form->isSubmitted()) {
            // IMPORTANT: This component always sets the htmx target and swap to end of the surrounding page <body>.
            // That ignores hx-target and hx-swap in the triggering element, which you can omit.
            header('HX-Retarget: body');
            header('HX-Reswap: beforeend');
        }

        // Send HX event headers
        if (count($this->hxTriggers)) {
            header(sprintf('HX-Trigger: %s', json_encode($this->hxTriggers)));
        }

        return $this->show();
    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $values = $form->getFieldValues();
        $this->payment->mapForm($values);

        // Check that the invoice is in the unpaid status.
        if ($this->invoice->status != \App\Db\Invoice::STATUS_UNPAID) {
            $form->addFieldError('method', 'You can only add payments to invoices with a status of `Unpaid`.');
        }

        // Check the payment amount does not exceed the invoice remainder amount (can be less)
        if ($this->payment->amount->greaterThan($this->invoice->unpaidTotal)) {
            $form->addFieldError('price', 'The payment amount cannot exceed '.$this->invoice->unpaidTotal->toString().'.');
        }

        $form->addFieldErrors($this->payment->validate());
        if ($form->hasErrors()) {
            $this->hxTriggers['tkForm:onError'] = ['status' => 'err', 'errors' => $form->getAllErrors()];
            return;
        }

        $this->invoice->addPayment($this->payment);

        // Trigger HX events
        $this->hxTriggers['tkForm:afterSubmit'] = ['status' => 'ok'];
        $this->hxTriggers['tkForm:dialogclose'] = '#'.self::CONTAINER_ID;
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
<div class="modal fade" data-bs-backdrop="static" tabindex="-1" var="dialog" aria-hidden="true">
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
    const unpaid = '{$unpaidTotal}';
    const form   = '#{$this->form->getId()}';

    $(document).on('htmx:afterSettle', dialog, function(e) {
        tkInit(form);
    });

    // open the dialog as soon as HTMX settles
    tkInit(form);
    $(dialog).modal('show');

    // put focus field when dialog shows
    $(dialog).on('shown.bs.modal', function() {
        setTimeout(function() {
            $('input:not(:hidden), textarea, select', dialog).first().focus();
            $('[name=amount]', dialog).val(unpaid);
        }, 0);
    });

    // catch dialog finished handling post request
    $(document).on('tkForm:dialogclose', function(e) {
        $(dialog).modal('hide');
    });

    // remove the dialog element from the dom when it closes
    $(dialog).on('hidden.bs.modal', function() {
        $(dialog).remove();
    });

});
</script>
</div>
HTML;
        return Template::load($html);
    }

}
