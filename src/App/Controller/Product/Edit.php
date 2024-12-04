<?php
namespace App\Controller\Product;

use App\Db\Product;
use App\Db\ProductCategory;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Factory;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Collection;
use Tk\Exception;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\InputGroup;
use Tk\Form\Field\Textarea;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Uri;

class Edit extends ControllerAdmin
{
    protected ?Product $product = null;
    protected ?Form  $form = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Edit Product');

        $this->setAccess(User::PERM_SYSADMIN);

        $productId = intval($_GET['productId'] ?? 0);

        $this->product = new Product();
        if ($productId) {
            $this->product = Product::find($productId);
            if (!($this->product instanceof Product)) {
                throw new Exception("invalid productId $productId");
            }
        }

        // Get the form template
        $this->form = new Form();

        $categories = ProductCategory::findFiltered([]);
        $list = Collection::toSelectList($categories, 'productCategoryId');
        $this->form->appendField(new Select('categoryId', $list))->prependOption('-- Select --', '');

        $this->form->appendField(new Input('name'));
        $this->form->appendField(new Input('code'));
        $this->form->appendField(new InputGroup('price', '$'));
        $this->form->appendField(new Select('recur', Product::RECURRING_LIST))->setLabel('Recurring Period');
        $this->form->appendField(new Checkbox('active', ['1' => 'Active']))->setLabel('&nbsp;');

        $this->form->appendField(new Textarea('description'))->addCss('mce-min');

        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', Uri::create('/productManager')));

        $load = $this->form->unmapModel($this->product);
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $form->mapModel($this->product);

        $form->addFieldErrors($this->product->validate());
        if ($form->hasErrors()) {
            return;
        }

        $isNew = ($this->product->productId == 0);
        $this->product->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create()->set('productId', $this->product->productId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Factory::instance()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        $this->form->getField('name')->addFieldCss('col-6');
        $this->form->getField('code')->addFieldCss('col-6');
        $this->form->getField('price')->addFieldCss('col-4');
        $this->form->getField('recur')->addFieldCss('col-4');
        $this->form->getField('active')->addFieldCss('col-4');

        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', Factory::instance()->getBackUrl());

        $template->appendTemplate('content', $this->form->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-shopping-cart"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}