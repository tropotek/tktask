<?php
namespace App\Controller\Company;

use App\Db\Company;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Factory;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Collection;
use Tk\Date;
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
    protected ?Company $company = null;
    protected ?Form  $form = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Edit Company');

        $this->setUserAccess(User::PERM_SYSADMIN);

        $companyId = intval($_GET['companyId'] ?? 0);

        $this->company = new Company();
        if ($companyId) {
            $this->company = Company::find($companyId);
            if (!($this->company instanceof Company)) {
                throw new Exception("invalid companyId $companyId");
            }
        }

        // Get the form template
        $this->form = new Form();

        $tab = "";

        $this->form->appendField(new Input('name'))
            ->setGroup($tab)
            ->setRequired();

        $this->form->appendField((new Select('type', Collection::listCombine(Company::TYPE_LIST)))
            ->prependOption('-- Select --', ''))
            ->setGroup($tab)
            ->setRequired();

        $this->form->appendField(new Input('email'))
            ->setGroup($tab)
            ->setRequired();
        $this->form->appendField(new Input('accountsEmail'))
            ->setGroup($tab);

        $this->form->appendField(new Input('contact'))
            ->setGroup($tab);

        $this->form->appendField(new Input('phone'))
            ->setGroup($tab);
        $this->form->appendField(new Input('abn'))
            ->setGroup($tab);
        $this->form->appendField(new Input('website'))
            ->setGroup($tab);
        $this->form->appendField(new Input('alias'))
            ->setGroup($tab);

        $this->form->appendField(new Input('address'))
            ->setGroup($tab);

        $this->form->appendField(new Checkbox('active', ['1' => 'Active']))->setLabel('');

        $this->form->appendField(new Textarea('notes'))
            ->addCss('mce-min')
            ->setGroup($tab);

        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', Uri::create('/companyManager')));

        $load = $this->company->unmapForm();
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $values = $form->getFieldValues();
        $this->company->mapForm($values);

        $form->addFieldErrors($this->company->validate());
        if ($form->hasErrors()) {
            return;
        }

        $isNew = ($this->company->companyId == 0);
        $this->company->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create()->set('companyId', $this->company->companyId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Factory::instance()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        $this->form->getField('name')->addFieldCss('col-6');
        $this->form->getField('type')->addFieldCss('col-6');
        $this->form->getField('email')->addFieldCss('col-6');
        $this->form->getField('accountsEmail')->addFieldCss('col-6');
        $this->form->getField('contact')->addFieldCss('col-6');
        $this->form->getField('phone')->addFieldCss('col-6');
        $this->form->getField('abn')->addFieldCss('col-4');
        $this->form->getField('website')->addFieldCss('col-4');
        $this->form->getField('alias')->addFieldCss('col-4');

        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', Factory::instance()->getBackUrl());

        if ($this->company->companyId) {
            $template->setVisible('edit');
            $template->setText('modified', $this->company->modified->format(Date::FORMAT_LONG_DATETIME));
            $template->setText('created', $this->company->created->format(Date::FORMAT_LONG_DATETIME));
        }

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
    <div class="card-header">
      <div class="info-dropdown dropdown float-end" title="Details" choice="edit">
        <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
        <div class="dropdown-menu dropdown-menu-end">
          <p class="dropdown-item"><span class="d-inline-block">Modified:</span> <span var="modified">...</span></p>
          <p class="dropdown-item"><span class="d-inline-block">Created:</span> <span var="created">...</span></p>
        </div>
      </div>
      <i class="fa fa-building"></i> <span var="title"></span>
    </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}