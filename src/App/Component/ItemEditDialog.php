<?php
namespace App\Component;

use App\Db\Invoice;
use App\Db\InvoiceItem;
use App\Db\Product;
use App\Db\User;
use App\Form\Field\Datalist;
use Bs\Mvc\ComponentInterface;
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

class ItemEditDialog extends \Dom\Renderer\Renderer implements ComponentInterface
{
    const string CONTAINER_ID = 'invoice-edit-item-dialog';

    protected ?Form        $form       = null;
    protected ?Invoice     $invoice    = null;
    protected ?InvoiceItem $item       = null;
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

        $this->item = new InvoiceItem();

        $this->form = new Form($this->item, 'form-item-edit');
        $this->form->setAction('');
        $this->form->setAttr('hx-post', Uri::create('/component/itemEditDialog', ['invoiceId' => $this->invoice->invoiceId]));
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

        $this->item = \App\Db\InvoiceItem::create(
            $values['productCode'] ?? '',
            $values['description'] ?? '',
            \Tk\Money::parseFromString($values['price'] ?? '0'),
            floatval($values['qty'] ?? 1)
        );

        $form->addFieldErrors($this->item->validate());
        if ($form->hasErrors()) {
            $this->hxTriggers['tkForm:onError'] = ['status' => 'err', 'errors' => $form->getAllErrors()];
            return;
        }

        $this->invoice->addItem($this->item);

        // Trigger HX events
        $this->hxTriggers['tkForm:afterSubmit'] = [
            'status' => 'ok',
            'target' => '#'.self::CONTAINER_ID,
        ];
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
<div class="modal fade" data-bs-backdrop="static" tabindex="-1" aria-hidden="true" var="dialog">
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


    $(document).on('htmx:afterSettle', dialog, function(e) {
        tkInit(form);
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
