<?php
namespace App\Controller\Domain;

use App\Db\Company;
use App\Db\Domain;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Collection;
use Tk\Date;
use Tk\Db\Filter;
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
    protected ?Domain $domain = null;
    protected ?Form  $form = null;


    public function doDefault(): void
    {
        $this->setUserAccess(User::PERM_SYSADMIN);
        $this->getPage()->setTitle('Edit Domain', 'fa fa-edit');

        $domainId = intval($_GET['domainId'] ?? 0);

        $this->domain = new Domain();
        if ($domainId) {
            $this->domain = Domain::find($domainId);
            if (!($this->domain instanceof Domain)) {
                throw new Exception("invalid domainId $domainId");
            }
        }

        // Get the form template
        $this->form = new Form();

        $companies = Company::findFiltered(Filter::create(['type' => Company::TYPE_CLIENT], 'name'));
        $list = Collection::toSelectList($companies, 'companyId');
        $fld = $this->form->appendField((new Select('companyId', $list))
            ->setLabel('Client')
            ->prependOption('-- Select --', '')
            ->setRequired()
        );

        $this->form->appendField(new Input('url'))
            ->setRequired();

        $this->form->appendField(new Checkbox('active', ['1' => 'Active']))
            ->setLabel('')
            ->addFieldCss('col-md-4');

        $this->form->appendField(new Textarea('notes'));

        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', Uri::create('/domainManager')));

        $load = $this->domain->unmapForm();
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);
    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $values = $form->getFieldValues();
        $this->domain->mapForm($values);

        $form->addFieldErrors($this->domain->validate());
        if ($form->hasErrors()) {
            return;
        }

        $isNew = ($this->domain->domainId == 0);
        $this->domain->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create()->set('domainId', $this->domain->domainId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect($this->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $template->setText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        if ($this->domain->domainId) {
            $template->setText('modified', $this->domain->modified->format(Date::FORMAT_LONG_DATETIME));
            $template->setText('created', $this->domain->created->format(Date::FORMAT_LONG_DATETIME));
            $template->setVisible('edit');
        }

        $template->appendTemplate('content', $this->form->show());

        if ($this->domain->domainId) {
            $url = Uri::create('/component/pingTable', ['domainId' => $this->domain->domainId]);
            $template->setAttr('ping-table', 'hx-get', $url);
            $template->setVisible('components');
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="row">
  <div class="col">
    <div class="card mb-3">
      <div class="card-header">
        <i var="icon"></i> <span var="title"></span>
        <div class="info-dropdown dropdown" title="Details" choice="edit">
          <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
          <div class="dropdown-menu dropdown-menu-end">
            <p class="dropdown-item"><span class="d-inline-block">Modified:</span> <span var="modified">...</span></p>
            <p class="dropdown-item"><span class="d-inline-block">Created:</span> <span var="created">...</span></p>
          </div>
        </div>
      </div>
      <div class="card-body" var="content"></div>
    </div>
  </div>
  <div class="col-5" choice="components">
     <div hx-get="/component/pingTable" hx-trigger="load" hx-swap="outerHTML" var="ping-table">
       <p class="text-center mt-4"><i class="fa fa-fw fa-spin fa-spinner fa-3x"></i><br>Loading...</p>
     </div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}