<?php
namespace App\Controller\Product;

use App\Db\Product;
use App\Db\ProductCategory;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Factory;
use Bs\Mvc\Form;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Alert;
use Tk\Collection;
use Tk\Date;
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
        $this->getPage()->setTitle('Edit Product', 'fa fa-shopping-cart');

        $this->setUserAccess(User::PERM_SYSADMIN);

        $productId = intval($_REQUEST['productId'] ?? 0);

        $this->product = new Product();
        if ($productId) {
            $this->product = Product::find($productId);
            if (is_null($this->product)) {
                throw new Exception("invalid productId $productId");
            }
        }

        // Get the form template
        $this->form = new Form();

        $categories = ProductCategory::findFiltered([]);
        $list = Collection::toSelectList($categories, 'productCategoryId');
        $this->form->appendField((new Select('productCategoryId', $list))
            ->prependOption('-- Select --', '')
            ->setRequired()
        );

        $this->form->appendField(new Input('name'))
            ->addFieldCss('col-md-6')
            ->setRequired();
        $this->form->appendField(new Input('code'))
            ->addFieldCss('col-md-6');
        $this->form->appendField(new InputGroup('price', '$'))
            ->addFieldCss('col-md-4');
        $this->form->appendField(new Select('cycle', Product::CYCLE_LIST))
            ->setLabel('Recurring')
            ->addFieldCss('col-md-4');
        $this->form->appendField(new Checkbox('active', ['1' => 'Active']))
            ->setLabel('&nbsp;')
            ->addFieldCss('col-md-4');

        $this->form->appendField(new Textarea('description'))->addCss('mce-min');

        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', Uri::create('/productManager')));

        $load = $this->product->unmapForm();
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $values = $form->getFieldValues();
        $this->product->mapForm($values);

        $form->addFieldErrors($this->product->validate());
        if ($form->hasErrors()) {
            return;
        }

        $isNew = ($this->product->productId == 0);
        $this->product->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create()->set('productId', $this->product->productId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Breadcrumbs::getBackUrl());
        }
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        if ($this->product->productId) {
            $template->setVisible('edit');
            $template->setText('modified', $this->product->modified->format(Date::FORMAT_LONG_DATETIME));
            $template->setText('created', $this->product->created->format(Date::FORMAT_LONG_DATETIME));
        }

        $template->appendTemplate('content', $this->form->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header">
      <div class="info-dropdown dropdown float-end" title="Details" choice="edit">
        <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
        <div class="dropdown-menu dropdown-menu-end">
          <p class="dropdown-item"><span class="d-inline-block">Modified:</span> <span var="modified">...</span></p>
          <p class="dropdown-item"><span class="d-inline-block">Created:</span> <span var="created">...</span></p>
        </div>
      </div>
      <i var="icon"></i> <span var="title"></span>
    </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}