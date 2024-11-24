<?php
namespace App\Controller\ProductCategory;

use App\Db\ProductCategory;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Factory;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Exception;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Textarea;
use Tk\Form\Field\Hidden;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Uri;

class Edit extends ControllerAdmin
{
    protected ?ProductCategory $productCategory = null;
    protected ?Form  $form = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Edit Product Category');

        $this->setAccess(User::PERM_SYSADMIN);

        $productCategoryId = intval($_GET['productCategoryId'] ?? 0);

        $this->productCategory = new ProductCategory();
        if ($productCategoryId) {
            $this->productCategory = ProductCategory::find($productCategoryId);
            if (!($this->productCategory instanceof ProductCategory)) {
                throw new Exception("invalid productCategoryId $productCategoryId");
            }
        }

        // Get the form template
        $this->form = new Form();

        $this->form->appendField(new Input('name'));
        $this->form->appendField(new Textarea('description'));

        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', Uri::create('/productCategoryManager')));

        $load = $this->form->unmapModel($this->productCategory);
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $form->mapModel($this->productCategory);

        $form->addFieldErrors($this->productCategory->validate());
        if ($form->hasErrors()) {
            return;
        }

        $isNew = ($this->productCategory->productCategoryId == 0);
        $this->productCategory->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create()->set('productCategoryId', $this->productCategory->productCategoryId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Factory::instance()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
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
    <div class="card-header"><i class="fa fa-folder-open"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}