<?php
namespace App\Component;

use App\Db\Company;
use App\Db\User;
use Bs\Mvc\ComponentInterface;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Collection;
use Tk\Db\Filter;
use Tk\Exception;
use Tk\Form\Action\Link;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Input;
use Tk\Uri;

class CompanyEditDialog extends \Dom\Renderer\Renderer implements ComponentInterface
{
    const string CONTAINER_ID = 'company-edit-dialog';

    protected ?Form    $form       = null;
    protected ?Company $company    = null;
    protected array    $hxTriggers = [];


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $companyId = (int)($_REQUEST['companyId'] ?? 0);
        $type = trim($_REQUEST['type'] ?? Company::TYPE_SUPPLIER);

        if (empty($companyId)) {
            if (!in_array($type, Company::TYPE_LIST)) {
                throw new Exception("invalid company type supplied");
            }
            $this->company = new Company();
            $this->company->type = $type;
        } else {
            $this->company = Company::find($companyId);
            if (!($this->company instanceof Company)) {
                throw new Exception("invalid company id supplied");
            }
        }

        $this->form = new Form($this->company, 'form-company-edit');
        $this->form->setAction('');
        $this->form->setAttr('hx-post', Uri::create());
        $this->form->setAttr('hx-swap', 'outerHTML');
        $this->form->setAttr('hx-target', "#{$this->form->getId()}");
        $this->form->setAttr('hx-select', "#{$this->form->getId()}");

        $this->form->appendField(new Input('name'))
            ->setRequired();
        $this->form->appendField(new Input('email'));
        if ($this->company->type == Company::TYPE_CLIENT) {
            $this->form->getField('email')->setRequired();
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

        $load = $this->company->unmapForm();
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

        if (!$this->form->isSubmitted()) {
            // Always set the htmx target and swap to end of the surrounding page <body>.
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
        $this->company->mapForm($values);

        $form->addFieldErrors($this->company->validate());
        if ($form->hasErrors()) {
            $this->hxTriggers['tkForm:error'] = ['status' => 'err', 'errors' => $form->getAllErrors()];
            return;
        }

        $this->company->save();

        $companies = Company::findFiltered(Filter::create(['type' => $this->company->type], 'name'));
        $list = Collection::toSelectList($companies, 'companyId');

        // Trigger HX events
        $this->hxTriggers['tkForm:afterSubmit'] = [
            'target' => '#' . self::CONTAINER_ID,
            'status' => 'ok',
            'companyId' => $this->company->companyId,
            'companies' => $list
        ];
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('dialog', 'id', $this->getDialogId());
        $template->setText('title', 'Add ' . $this->company->type);

        $this->form->getField('name')->addFieldCss('col-md-6');
        $this->form->getField('email')->addFieldCss('col-md-6');
        if ($this->company->type == Company::TYPE_CLIENT) {
            $this->form->getField('accountsEmail')->addFieldCss('col-md-6');
            $this->form->getField('contact')->addFieldCss('col-md-6');
        }
        $this->form->getField('phone')->addFieldCss('col-md-6');
        $this->form->getField('abn')->addFieldCss('col-md-6');
        $this->form->getField('website')->addFieldCss('col-md-6');
        $this->form->getField('address')->addFieldCss('col-md-6');

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
                <h4 class="modal-title" var="title">Add Company</h4>
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

    tkInit(form);
    $(dialog).modal('show');

    $(dialog).on('shown.bs.modal', function() {
        setTimeout(function() { $('input:not(:hidden), textarea, select', dialog).first().focus(); }, 0);
    });

    $(document).on('tkForm:afterSubmit', form, function(e) {
        $(dialog).modal('hide');
    });

    $(dialog).on('hidden.bs.modal', function() {
        $(dialog).remove();
    });
});
</script>
</div>
HTML;
        return Template::load($html);
    }

}
