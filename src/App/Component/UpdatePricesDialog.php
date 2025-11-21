<?php
namespace App\Component;

use App\Db\Product;
use App\Db\User;
use Bs\Mvc\ComponentInterface;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Form\Action\Link;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Hidden;
use Tk\Form\Field\InputGroup;
use Tk\Money;
use Tk\Uri;

class UpdatePricesDialog extends \Dom\Renderer\Renderer implements ComponentInterface
{
    const string CONTAINER_ID = 'invoice-update-prices-dialog';

    protected ?Form        $form       = null;
    protected array        $hxTriggers = [];


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $productIds = $_REQUEST['id'] ?? [];

        $this->form = new Form(null, 'form-update-prices');
        $this->form->setAction('');
        $this->form->addCss('mt-0');
        $this->form->setAttr('hx-post', Uri::create());
        $this->form->setAttr('hx-swap', 'outerHTML');
        $this->form->setAttr('hx-target', "#{$this->form->getId()}");
        $this->form->setAttr('hx-select', "#{$this->form->getId()}");

        foreach ($productIds as $i => $id) {
            $this->form->prependField(new Hidden('id['.$i.']', $id));
        }

        $this->form->appendField(new InputGroup('amount', '%'))
            ->setLabel('Price Change')
            ->setRequired();

        $this->form->appendField(new Link('cancel', Uri::create('#')))
            ->setAttr('data-bs-dismiss', 'modal')
            ->addCss('float-end');
        $this->form->appendField(new Submit('save', [$this, 'onSubmit']))
            ->addCss('float-end');

        $this->form->execute($_POST);

        if (!$this->form->isSubmitted()) {
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
        $amount = $form->getFieldValue('amount');
        if ($amount < -100 || $amount > 100) {
            $form->addFieldError('amount', 'The price change must be between -100 and 100.');
        }

        // get id array from request not field value (dynamically added fields)
        $ids = $_REQUEST['id'] ?? [];
        if (empty($ids)) {
            $form->addError('No valid products selected');
        }

        if ($form->hasErrors()) {
            $this->hxTriggers['tkForm:onError'] = ['status' => 'err', 'errors' => $form->getAllErrors()];
            return;
        }

        foreach ($ids as $id) {
            $product = Product::find($id);
            if ($product instanceof Product) {
                $add = intval(round($product->price->getAmount() * ($amount / 100)));
                $product->price = $product->price->add(Money::create($add));
                $product->save();
            }
        }

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

        $html = <<<HTML
<div class="modal fade" data-bs-backdrop="static" tabindex="-1" var="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Add Payment</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" var="content">
        <p class="mb-0"><strong class="text-danger">This action will change all selected product prices.</strong></p>
      </div>
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
        setTimeout(function() {
            $('input:not(:hidden), textarea, select', dialog).first().focus();
        }, 0);
    });

    // catch dialog finished handling post request
    $(document).on('tkForm:dialogclose', function(e) {
        $(dialog).modal('hide');
    });

    // remove the dialog element from the dom when it closes
    $(dialog).on('hidden.bs.modal', function() {
        $(dialog).remove();
        location.reload();
    });

});
</script>
</div>
HTML;
        return Template::load($html);
    }

}
