<?php
namespace App\Controller\Company;

use App\Db\Company;
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
    protected ?Company $company = null;
    protected ?Form  $form = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Edit Company');

        $companyId = intval($_GET['companyId'] ?? 0);

        $this->company = new Company();
        if ($companyId) {
            $this->company = Company::find($companyId);
            if (!($this->company instanceof Company)) {
                throw new Exception("invalid companyId $companyId");
            }
        }

        // todo: $this->setAccess(...);

        // Get the form template
        $this->form = new Form();

        $tab = "";

        $this->form->appendField(new Input('name'))
            ->setGroup($tab);

        $this->form->appendField((new Select('type', Company::TYPE_LIST))
            ->prependOption('-- Select --', ''))
            ->setGroup($tab);

        $this->form->appendField(new Input('email'))
            ->setGroup($tab);
        $this->form->appendField(new Input('contact'))
            ->setGroup($tab);
        $this->form->appendField(new Input('alias'))
            ->setGroup($tab);

        $this->form->appendField(new Input('phone'))
            ->setGroup($tab);
        $this->form->appendField(new Input('abn'))
            ->setGroup($tab);
        $this->form->appendField(new Input('website'))
            ->setGroup($tab);

        $this->form->appendField(new Input('address'))
            ->setGroup($tab);

        $this->form->appendField(new Checkbox('active'));

        $this->form->appendField(new Textarea('notes'))
            ->addCss('mce-min')
            ->setGroup($tab);

        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', Uri::create('/companyManager')));

        $load = $this->form->unmapModel($this->company);
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $form->mapModel($this->company);

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
        $this->form->getField('email')->addFieldCss('col-4');
        $this->form->getField('contact')->addFieldCss('col-4');
        $this->form->getField('alias')->addFieldCss('col-4');
        $this->form->getField('phone')->addFieldCss('col-4');
        $this->form->getField('abn')->addFieldCss('col-4');
        $this->form->getField('website')->addFieldCss('col-4');

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
    <div class="card-header"><i class="fa fa-building"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}