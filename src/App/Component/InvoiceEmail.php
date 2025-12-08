<?php

namespace App\Component;

use App\Db\Invoice;
use App\Db\User;
use Bs\Mvc\ComponentInterface;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Form\Action\Link;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Textarea;
use Tk\Uri;

class InvoiceEmail extends \Dom\Renderer\Renderer implements ComponentInterface
{
    const string CONTAINER_ID = 'invoice-email-dialog';

    protected ?Form     $form       = null;
    protected ?Invoice  $invoice    = null;
    protected array     $hxTriggers = [];


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()) return null;

        $invoiceId = intval($_REQUEST['invoiceId'] ?? 0);

        $this->invoice = Invoice::find($invoiceId);
        if (is_null($this->invoice)) {
            throw new \Exception('Path case not found');
        }

        $this->form = new Form(null, 'email-invoice');

        $this->form->appendField(new Textarea('message'))
            ->addCss('mce-xs')
            ->setLabel('Email Message');

        $this->form->appendField(new Link('cancel', Uri::create('#')))
            ->setAttr('data-bs-dismiss', 'modal')
            ->addCss('float-end');

        $this->form->appendField(new Submit('send', [$this, 'onSubmit']))
            ->setLabel('Email Invoice')
            ->addCss('float-end');

        $this->form->execute($_POST);

        if (!$this->form->isSubmitted()) {
            // Always set the htmx target and swap to end of the surrounding page <body>.
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

        $message = trim($form->getFieldValue('message'));

        if ($form->hasErrors()) {
            $this->hxTriggers['tkForm:onError'] = [
                'status' => 'err',
                'errors' => $form->getAllErrors()
            ];
            return;
        }

        if (!\App\Email\Invoice::sendIssueInvoice($this->invoice, $message)) {
            Alert::addError("Failed to send invoice id {$this->invoice->invoiceId}");
        }

        // Trigger HX events
        $this->hxTriggers['tkForm:afterSubmit'] = [
            'status' => 'ok',
        ];
        $this->hxTriggers['tkForm:dialogclose'] = '#'.self::CONTAINER_ID;
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('dialog', 'id', $this->getDialogId());
        $this->form->getRenderer()->getTemplate()->addCss('actions', 'mt-4 float-end');
        $this->form->getRenderer()->getTemplate()->removeCss('fields', 'g-3 mt-1')->addCss('fields', 'g-2');

        $template->appendTemplate('content', $this->form->htmxShow());

        return $template;
    }

    public function getDialogId(): string
    {
        return self::CONTAINER_ID;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="modal fade" data-bs-backdrop="static" tabindex="-1" aria-hidden="true" var="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Email Invoice</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" var="content"></div>
    </div>
  </div>

<script>
jQuery(function($) {
    const dialog = '#{$this->getDialogId()}';
    const form   = '#{$this->form->getId()}';

    $(document).on('htmx:afterSettle', dialog, function(e) {
        tkInit(form);
    });

    $(document).on('htmx:beforeRequest', function(e) {
        if ($(e.detail.elt).is(form) && e.detail.requestConfig.verb === 'post') {
            // set the description value as tinymce is not in the HTMX dom tree
            e.detail.requestConfig.parameters['message'] = tinymce.activeEditor.getContent();
        }
    });

    // open the dialog as soon as HTMX settles
    tkInit(form);
    $(dialog).modal('show');

    // put focus field when dialog shows
    $(dialog).on('shown.bs.modal', function() {
        setTimeout(function() { $('input:not(:hidden), textarea, select', dialog).first().focus(); }, 0);
    });

    // catch dialog finished handling post request
    $(document).on('tkForm:afterSubmit', function(e) {
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
