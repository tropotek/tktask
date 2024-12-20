<?php
namespace App\Component;

use App\Db\Invoice;
use App\Db\InvoiceItem;
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
use Tk\Log;
use Tk\Uri;

class ItemAddDialog extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    protected string       $dialogId = 'invoice-add-item';
    protected ?Form        $form     = null;
    protected array        $hxEvents = [];
    protected ?Invoice     $invoice  = null;
    protected ?InvoiceItem $item     = null;


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $invoiceId = (int)($_POST['invoiceId'] ?? $_GET['invoiceId'] ?? 0);
        $this->invoice = Invoice::find($invoiceId);
        if (!($this->invoice instanceof Invoice)) {
            Log::error("invalid invoice ID {$invoiceId}");
            return null;
        }

        $this->item = new InvoiceItem();

        $this->form = new Form($this->item);
        $this->form->setAction('');
        $this->form->setAttr('hx-post', Uri::create('/component/itemAddDialog', ['invoiceId' => $this->invoice->invoiceId]));
        $this->form->setAttr('hx-swap', 'outerHTML');
        $this->form->setAttr('hx-target', "#{$this->form->getId()}");
        $this->form->setAttr('hx-select', "#{$this->form->getId()}");

        $products = Product::findFiltered(Db\Filter::create(['active' => true]));
        $list = Collection::toSelectList($products, 'productId', 'name');
        $this->form->appendField(new Datalist('description', $list));
        $this->form->appendField(new Input('productCode'));
        $this->form->appendField(new InputGroup('price', '$'));
        $this->form->appendField(new Input('qty', 'number'));

        $this->form->appendField(new Link('cancel', Uri::create('#')))
            ->setAttr('data-bs-dismiss', 'modal')
            ->addCss('float-end');
        $this->form->appendField(new Submit('save', [$this, 'onSubmit']))
            ->addCss('float-end');

        $load = $this->form->unmapModel($this->item);
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
        $vals = $form->getFieldValues();

        $this->item = \App\Db\InvoiceItem::create(
            $vals['productCode'] ?? '',
            $vals['description'] ?? '',
            \Tk\Money::parseFromString($vals['price'] ?? '0'),
            floatval($vals['qty'] ?? 1)
        );

        $form->addFieldErrors($this->item->validate());
        if ($form->hasErrors()) {
            $this->hxEvents['tkForm:onError'] = ['status' => 'err', 'errors' => $form->getAllErrors()];
            return;
        }

        $this->invoice->addItem($this->item);

        // Trigger HX events
        $this->hxEvents['tkForm:afterSubmit'] = ['status' => 'ok'];
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('dialog', 'id', $this->getDialogId());

        $this->form->getField('description')->addFieldCss('col-8');
        $this->form->getField('productCode')->addFieldCss('col-4');
        $this->form->getField('price')->addFieldCss('col-6');
        $this->form->getField('qty')->addFieldCss('col-6');

        $this->form->getRenderer()->getTemplate()->addCss('actions', 'mt-4 float-end');

        $template->appendTemplate('content', $this->form->show());

        $js = <<<JS
jQuery(function($) {
    const dialog = '#{$this->getDialogId()}';

    // reload page after successfull submit
    $(document).on('tkForm:afterSubmit', function(e) {
        $(dialog).modal('hide');
        location = location.href;
    });

    // reset form fields
    $(dialog).on('show.bs.modal', function(e) {
        $('[name=description]', this).val('');
        $('[name=productCode]', this).val('');
        $('[name=price]', this).val('0.00');
        $('[name=qty]', this).val('1');
        $('.is-invalid', this).removeClass('is-invalid');
    });

    // auto-complete select for invoiceable products
    $('[name=description]', dialog).on('change', function() {
        let selected = $('option[value="' + $(this).val() + '"]', $(this).next());
        if (!selected.length) return;
        let productId = selected.data('value');
         $.get(tkConfig.baseUrl + '/api/getProduct', {productId})
         .done(function(data) {
            $('[name=productCode]', dialog).val(data.code);
            $('[name=price]', dialog).val(data.price);
         });
    });
});
JS;
        $template->appendJs($js);
        return $template;
    }

    public function getDialogId(): string
    {
        return $this->dialogId;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="modal fade" data-bs-backdrop="static" var="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Add Item</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" var="content"></div>
    </div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}
