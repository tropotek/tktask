<?php
namespace App\Component;

use App\Db\Invoice;
use App\Db\InvoiceItem;
use App\Db\Product;
use App\Db\StatusLog;
use App\Db\User;
use App\Form\Field\Datalist;
use App\Form\Field\StatusSelect;
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

class InvoiceEditDialog extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    const string CONTAINER_ID = 'invoice-edit-dialog';

    protected ?Form    $form     = null;
    protected array    $hxEvents = [];
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

        $this->form = new Form($this->invoice, 'frm-invoice');
        $this->form->removeAttr('action');
        $this->form->setAttr('hx-post', Uri::create('/component/invoiceEditDialog', ['invoiceId' => $this->invoice->invoiceId]));
        $this->form->setAttr('hx-swap', 'outerHTML');
        $this->form->setAttr('hx-target', "#{$this->form->getId()}");
        $this->form->setAttr('hx-select', "#{$this->form->getId()}");


        $this->form->appendField(new InputGroup('discount', '%'));
        $this->form->appendField(new InputGroup('tax', '%'));
        $this->form->appendField(new InputGroup('shipping', '$'));

        $this->form->appendField(new InputGroup('purchaseOrder', '#'));

        $list = \App\Db\Invoice::STATUS_LIST;
        //$this->form->appendField(new StatusSelect('status', $list))->setAttr('data-message-text', 'off');
        $this->form->appendField(new Select('status', $list));

        $this->form->appendField(new Textarea('notes'))->addCss('mce-min');


        $this->form->appendField(new Link('cancel', Uri::create('#')))
            ->setAttr('data-bs-dismiss', 'modal')
            ->addCss('float-end');
        $this->form->appendField(new Submit('save', [$this, 'onSubmit']))
            ->addCss('float-end');

        $load = $this->invoice->unmapForm();
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
        $values = $form->getFieldValues();
        $this->invoice->mapForm($values);

        $form->addFieldErrors($this->invoice->validate());
        if ($form->hasErrors()) {
            $this->hxEvents['tkForm:onError'] = ['status' => 'err', 'errors' => $form->getAllErrors()];
            return;
        }

        $this->invoice->save();

        StatusLog::create($this->invoice, trim($_POST['status_msg'] ?? ''), truefalse($_POST['status_notify'] ?? false));

        // Trigger HX events
        $this->hxEvents['tkForm:afterSubmit'] = ['status' => 'ok'];
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('dialog', 'id', $this->getDialogId());

        $this->form->getField('discount')->addFieldCss('col-4');
        $this->form->getField('tax')->addFieldCss('col-4');
        $this->form->getField('shipping')->addFieldCss('col-4');
        $this->form->getField('purchaseOrder')->addFieldCss('col-6');
        $this->form->getField('status')->addFieldCss('col-6');

        $this->form->getRenderer()->getTemplate()->addCss('actions', 'mt-4 float-end');
        $this->form->getRenderer()->getTemplate()->removeCss('fields', 'g-3 mt-1')->addCss('fields', 'g-2');

        $template->appendTemplate('content', $this->form->show());

        $js = <<<JS

JS;
        $template->appendJs($js);
        return $template;
    }

    public function getDialogId(): string
    {
        return self::CONTAINER_ID;
    }

    public function __makeTemplate(): ?Template
    {
        $baseUrl = Uri::create('/component/invoiceEditDialog', ['invoiceId' => $this->invoice->invoiceId])->toString();

        $html = <<<HTML
<div class="modal fade" data-bs-backdrop="static" var="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Invoice Edit</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" var="content"></div>
    </div>
  </div>
<script>
  jQuery(function($) {
    const dialog    = '#{$this->getDialogId()}';
    const form      = '#{$this->form->getId()}';
    const baseUrl   = '$baseUrl';

    // reload page after successfull submit
    $(document).on('htmx:afterSettle', function(e) {
        if (!$(e.detail.elt).is(form)) return;
        if (e.detail.requestConfig.verb === 'get') {
            tkInit(form);
        }
    });
    $(document).on('htmx:beforeRequest', function(e) {
        if ($(e.detail.elt).is(form) && e.detail.requestConfig.verb === 'post') {
            // set the description value as tinymce is not in the HTMX dom tree
            e.detail.requestConfig.parameters['notes'] = tinymce.activeEditor.getContent();
        }
    });

    // reload page after successfull submit
    $(document).on('tkForm:afterSubmit', function(e) {
        if (!$(e.detail.elt).is(form)) return;
        $(dialog).modal('hide');
    });

    // reset form fields
    $(dialog).on('show.bs.modal', function(e) {
        // reload form to refresh vals
        htmx.ajax('get', baseUrl, {
            source:    form,
            target:    form,
            swap:      'outerHTML'
        });
    });

});
</script>
</div>
HTML;
        return Template::load($html);
    }

}
