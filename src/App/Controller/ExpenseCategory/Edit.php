<?php
namespace App\Controller\ExpenseCategory;

use App\Db\ExpenseCategory;
use App\Db\StatusLog;
use App\Db\User;
use App\Form\Field\StatusSelect;
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
use Tk\Form\Field\InputGroup;
use Tk\Form\Field\Textarea;
use Tk\Form\Field\Hidden;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Uri;

class Edit extends ControllerAdmin
{
    protected ?ExpenseCategory $expenseCategory = null;
    protected ?Form  $form = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Edit Expense Category');

        $this->setUserAccess(User::PERM_SYSADMIN);

        $expenseCategoryId = intval($_GET['expenseCategoryId'] ?? 0);

        $this->expenseCategory = new ExpenseCategory();
        if ($expenseCategoryId) {
            $this->expenseCategory = ExpenseCategory::find($expenseCategoryId);
            if (!($this->expenseCategory instanceof ExpenseCategory)) {
                throw new Exception("invalid expenseCategoryId $expenseCategoryId");
            }
        }

        // Get the form template
        $this->form = new Form();
        $this->form->appendField(new Input('name'));
        $this->form->appendField(new InputGroup('ratio', '%'));

        $this->form->appendField(new Checkbox('active', ['1' => 'Active']))->setLabel('&nbsp;');
        $this->form->appendField(new Textarea('description'));

        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', Uri::create('/expenseCategoryManager')));

        $load = $this->expenseCategory->unmapForm();
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $values = $form->getFieldValues();
        $this->expenseCategory->mapForm($values);

        $form->addFieldErrors($this->expenseCategory->validate());
        if ($form->hasErrors()) {
            return;
        }

        $isNew = ($this->expenseCategory->expenseCategoryId == 0);
        $this->expenseCategory->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create()->set('expenseCategoryId', $this->expenseCategory->expenseCategoryId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Factory::instance()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        $this->form->getField('name')->addFieldCss('col-4');
        $this->form->getField('ratio')->addFieldCss('col-4');
        $this->form->getField('active')->addFieldCss('col-4');

        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', Factory::instance()->getBackUrl());

        if ($this->expenseCategory->expenseCategoryId) {
            $template->setVisible('edit');
            $template->setText('modified', $this->expenseCategory->modified->format(Date::FORMAT_LONG_DATETIME));
            $template->setText('created', $this->expenseCategory->created->format(Date::FORMAT_LONG_DATETIME));
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