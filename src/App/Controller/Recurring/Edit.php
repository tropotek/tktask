<?php
namespace App\Controller\Recurring;

use App\Db\Company;
use App\Db\Product;
use App\Db\Recurring;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Factory;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Collection;
use Tk\Db\Filter;
use Tk\Exception;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\InputGroup;
use Tk\Form\Field\Textarea;
use Tk\Form\Field\Hidden;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Uri;

class Edit extends ControllerAdmin
{
    protected ?Recurring $recurring = null;
    protected ?Form  $form = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Edit Recurring');

        if (!User::getAuthUser()?->isStaff()) {
            Alert::addWarning('You do not have permission to access this page');
                User::getAuthUser()?->getHomeUrl()->redirect() ?? Uri::create('/')->redirect();
        }

        $recurringId = intval($_GET['recurringId'] ?? 0);

        $this->recurring = new Recurring();
        if ($recurringId) {
            $this->recurring = Recurring::find($recurringId);
            if (!($this->recurring instanceof Recurring)) {
                throw new Exception("invalid recurringId $recurringId");
            }
        }

        // Get the form template
        $this->form = new Form();

        $cats = Company::findFiltered(Filter::create(['type' => Company::TYPE_CLIENT], '-active, name'));
        $list = Collection::toSelectList($cats, 'companyId');
        $this->form->appendField((new Select('companyId', $list))
            ->prependOption('-- Select --', ''));
        $this->form->appendField(new Select('cycle', Recurring::CYCLE_LIST));


        $cats = Product::findFiltered(Filter::create([], 'name'));
        $list = Collection::toSelectList($cats, 'productId');
        $this->form->appendField((new Select('productId', $list))
            ->prependOption('-- Select --', '')
            ->addOnShowOption(function(\Dom\Template $template, \Tk\Form\Field\Option $option) {
                $product = Product::find(intval($option->getValue()));
                if ($product instanceof Product) {
                    $option->setAttr('data-price', $product->price->toFloatString());
                    $option->setAttr('data-name', $product->name);
                    $option->setAttr('data-cycle', $product->cycle);
                    $option->setName($option->getName() . ' [' . $product->price->toString() . ' - '.ucfirst($product->cycle).']');
                } else {
                    $option->setAttr('data-price', $this->recurring->price->toFloatString());
                    $option->setAttr('data-name', $this->recurring->description);
                    $option->setAttr('data-cycle', $this->recurring->cycle);
                }
            })
        );
        $this->form->appendField(new InputGroup('price', '$'));

        $this->form->appendField(new Input('description'));

        $this->form->appendField(new Input('startOn', 'date'));
        $this->form->appendField(new Input('endOn', 'date'));

        $this->form->appendField(new Checkbox('issue'));
        $this->form->appendField(new Checkbox('active'));

        $this->form->appendField(new Textarea('notes'));

        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', Uri::create('/recurringManager')));

        $load = $this->form->unmapModel($this->recurring);
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $form->mapModel($this->recurring);

        $form->addFieldErrors($this->recurring->validate());
        if ($form->hasErrors()) {
            return;
        }

        $isNew = ($this->recurring->recurringId == 0);
        $this->recurring->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create()->set('recurringId', $this->recurring->recurringId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Factory::instance()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        $this->form->getField('companyId')->addFieldCss('col-6');
        $this->form->getField('cycle')->addFieldCss('col-6');
        $this->form->getField('productId')->addFieldCss('col-6');
        $this->form->getField('price')->addFieldCss('col-6');
        $this->form->getField('description')->addFieldCss('col-12');
        $this->form->getField('startOn')->addFieldCss('col-6');
        $this->form->getField('endOn')->addFieldCss('col-6');
        $this->form->getField('issue')->addFieldCss('col-6');
        $this->form->getField('active')->addFieldCss('col-6');

        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', Factory::instance()->getBackUrl());

        $template->appendTemplate('content', $this->form->show());

        $js = <<<JS
jQuery(function ($) {
    let form = $('#{$this->form->getId()}');

    $('[name=productId]', form).on('change', function() {
        let option = $(':selected', this);
        if (option.data('price')) {
            $('[name=price]', form).val(option.data('price'));
        }
        if (option.data('name')) {
            $('[name=description]', form).val(option.data('name'));
        }
        if (option.data('cycle')) {
            $('[name=cycle]', form).val(option.data('cycle'));
        }
    });
});
JS;
        $template->appendJs($js);

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
    <div class="card-header"><i class="fa fa-edit"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}