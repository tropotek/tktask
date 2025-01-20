<?php
namespace App\Controller\TaskCategory;

use App\Db\TaskCategory;
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

        $this->setAccess(User::PERM_SYSADMIN);

        // Get the form template
        $this->form = new Form();
        $this->form->appendField(new Input('name'));
        $this->form->appendField(new Input('label'));
        $this->form->appendField(new Input('description'));
        $this->form->appendField(new Checkbox('active', ['1' => 'Active']))->setLabel('');

        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', Uri::create('/taskCategoryManager')));

        $load = $this->form->unmapModel($this->taskCategory);
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $form->mapModel($this->taskCategory);

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