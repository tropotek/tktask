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
    const string CONTAINER_ID = 'invoice-add-item-dialog';

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
        $this->form->appendField(new Datalist('description', $list))
            ->setRequired();
        $this->form->appendField(new Input('productCode'))
            ->setRequired();
        $this->form->appendField(new InputGroup('price', '$'));
        $this->form->appendField(new Input('qty', 'number'));

        $this->form->appendField(new Link('cancel', Uri::create('#')))
            ->setAttr('data-bs-dismiss', 'modal')
            ->addCss('float-end');
        $this->form->appendField(new Submit('save', [$this, 'onSubmit']))
            ->addCss('float-end');

        $load = $this->item->unmapForm();
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

        $this->item = \App\Db\InvoiceItem::create(
            $values['productCode'] ?? '',
            $values['description'] ?? '',
            \Tk\Money::parseFromString($values['price'] ?? '0'),
            floatval($values['qty'] ?? 1)
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

        $this->form->getField('description')->addFieldCss('col-md-8');
        $this->form->getField('productCode')->addFieldCss('col-md-4');
        $this->form->getField('price')->addFieldCss('col-md-6');
        $this->form->getField('qty')->addFieldCss('col-md-6');

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

<script>
  jQuery(function($) {
    const dialog = '#{$this->getDialogId()}';
    const form   = '#{$this->form->getId()}';

    // reload page after successfull submit
    $(document).on('tkForm:afterSubmit', function(e) {
        if (!$(e.detail.elt).is(form)) return;
        $(dialog).modal('hide');
        location = location.href;
    });

    // reset form fields
    $(dialog).on('show.bs.modal', function() {
        $('[name=description]', this).val('');
        $('[name=productCode]', this).val('');
        $('[name=price]', this).val('0.00');
        $('[name=qty]', this).val('1');
        $('.is-invalid', this).removeClass('is-invalid');
    });

    // auto-complete select for invoicable products
    $('[name=description]', dialog).on('input', function() {
        let options = $('option', '#' + $(this).attr('list'));
        let val = $(this).val();
        let selected = null;

        options.each(function() {
            if ($(this).val() === val) {
                selected = $(this);
            }
        });

        if (selected) {
            let productId = selected.data('value');
            $.get(tkConfig.baseUrl + '/api/getProduct', {productId})
            .done(function(data) {
               $('[name=productCode]', dialog).val(data.code);
               $('[name=price]', dialog).val(data.price);
            });
        }
    });
  });
</script>
</div>
HTML;
        return Template::load($html);
    }

}
