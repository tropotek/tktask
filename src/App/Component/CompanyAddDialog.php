<?php
namespace App\Component;

use App\Db\Company;
use App\Db\User;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Collection;
use Tk\Db\Filter;
use Tk\Exception;
use Tk\Form\Action\Link;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Input;
use Tk\Uri;

class CompanyAddDialog extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    protected string   $dialogId = 'company-add-item';
    protected ?Form    $form     = null;
    protected array    $hxEvents = [];
    protected ?Company $company  = null;
    protected string   $type     = Company::TYPE_SUPPLIER;


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $this->type = trim($_GET['type'] ?? $_POST['type'] ?? Company::TYPE_SUPPLIER);
        if (!in_array($this->type, Company::TYPE_LIST)) {
            throw new Exception("Invalid company type");
        }

        $this->company = new Company();
        $this->company->type = $this->type;

        $this->form = new Form($this->company);
        $this->form->setAction('');
        $this->form->setAttr('hx-post', Uri::create('/component/companyAddDialog'));
        $this->form->setAttr('hx-swap', 'outerHTML');
        $this->form->setAttr('hx-target', "#{$this->form->getId()}");
        $this->form->setAttr('hx-select', "#{$this->form->getId()}");

        $this->form->appendField(new Input('name'));
        $this->form->appendField(new Input('email'));
        if ($this->type == Company::TYPE_CLIENT) {
            $this->form->appendField(new Input('accountsEmail'));
            $this->form->appendField(new Input('contact'));
        }
        $this->form->appendField(new Input('phone'));
        $this->form->appendField(new Input('abn'));
        $this->form->appendField(new Input('website'));
        $this->form->appendField(new Input('address'));

        $this->form->appendField(new Link('cancel', Uri::create('#')))
            ->setAttr('data-bs-dismiss', 'modal')
            ->addCss('float-end');
        $this->form->appendField(new Submit('save', [$this, 'onSubmit']))
            ->addCss('float-end');

        $load = $this->form->unmapModel($this->company);
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
        $form->mapModel($this->company);


        $form->addFieldErrors($this->company->validate());
        if ($form->hasErrors()) {
            $this->hxEvents['tkForm:onError'] = ['status' => 'err', 'errors' => $form->getAllErrors()];
            return;
        }

        $this->company->save();

        // Trigger HX events
        $companies = Company::findFiltered(Filter::create(['type' => $this->type], 'name'));
        $list = Collection::toSelectList($companies, 'companyId');
        $this->hxEvents['tkForm:afterSubmit'] = ['status' => 'ok', 'companyId' => $this->company->companyId, 'companies' => $list];
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('dialog', 'id', $this->getDialogId());

        $this->form->getField('name')->addFieldCss('col-6');
        $this->form->getField('email')->addFieldCss('col-6');
        if ($this->type == Company::TYPE_CLIENT) {
            $this->form->getField('accountsEmail')->addFieldCss('col-6');
            $this->form->getField('contact')->addFieldCss('col-6');
        }
        $this->form->getField('phone')->addFieldCss('col-6');
        $this->form->getField('abn')->addFieldCss('col-6');
        $this->form->getField('website')->addFieldCss('col-6');
        $this->form->getField('address')->addFieldCss('col-6');

        $this->form->getRenderer()->getTemplate()->addCss('actions', 'mt-4 float-end');
        $this->form->getRenderer()->getTemplate()->removeCss('fields', 'g-3 mt-1')->addCss('fields', 'g-2');

        $template->appendTemplate('content', $this->form->show());

        $js = <<<JS
jQuery(function($) {
    const dialog = '#{$this->getDialogId()}';
    const form   = '#{$this->form->getId()}';

    // reload page after successfull submit
    $(document).on('tkForm:afterSubmit', function(e) {
        if (!$(e.detail.elt).is(form)) return;
        $(dialog).modal('hide');
    });

    // reset form fields
    $(dialog).on('show.bs.modal', function(e) {
        $('[name=name]', this).val('');
        $('[name=email]', this).val('');
        $('[name=phone]', this).val('');
        $('[name=abn]', this).val('');
        $('[name=website]', this).val('');
        $('[name=address]', this).val('');
        $('.is-invalid', this).removeClass('is-invalid');
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
        <h4 class="modal-title">Add Company</h4>
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
