<?php
namespace App\Controller\TaskCategory;

use App\Db\TaskCategory;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Factory;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Date;
use Tk\Exception;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Input;
use Tk\Uri;

class Edit extends ControllerAdmin
{
    protected ?TaskCategory $taskCategory = null;
    protected ?Form  $form = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Edit Task Category');

        $taskCategoryId = intval($_GET['taskCategoryId'] ?? 0);

        $this->taskCategory = new TaskCategory();
        if ($taskCategoryId) {
            $this->taskCategory = TaskCategory::find($taskCategoryId);
            if (!($this->taskCategory instanceof TaskCategory)) {
                throw new Exception("invalid taskCategoryId $taskCategoryId");
            }
        }

        $this->setUserAccess(User::PERM_SYSADMIN);

        // Get the form template
        $this->form = new Form();
        $this->form->appendField(new Input('name'));
        $this->form->appendField(new Input('label'));
        $this->form->appendField(new Input('description'));
        $this->form->appendField(new Checkbox('active', ['1' => 'Active']))->setLabel('');

        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', Uri::create('/taskCategoryManager')));

        $load = $this->taskCategory->unmapForm();
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $values = $form->getFieldValues();
        $this->taskCategory->mapForm($values);

        $form->addFieldErrors($this->taskCategory->validate());
        if ($form->hasErrors()) {
            return;
        }

        $isNew = ($this->taskCategory->taskCategoryId == 0);
        $this->taskCategory->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create()->set('taskCategoryId', $this->taskCategory->taskCategoryId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Factory::instance()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        $this->form->getField('name')->addFieldCss('col-6');
        $this->form->getField('label')->addFieldCss('col-6');

        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', Factory::instance()->getBackUrl());

        if ($this->taskCategory->taskCategoryId) {
            $template->setVisible('edit');
            $template->setText('modified', $this->taskCategory->modified->format(Date::FORMAT_LONG_DATETIME));
            $template->setText('created', $this->taskCategory->created->format(Date::FORMAT_LONG_DATETIME));
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
      <i class="fa fa-folder-open"></i> <span var="title"></span>
    </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}