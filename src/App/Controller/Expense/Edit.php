<?php
namespace App\Controller\Expense;

use App\Component\Files;
use App\Component\CompanyAddDialog;
use App\Db\Company;
use App\Db\Expense;
use App\Db\ExpenseCategory;
use App\Db\User;
use App\Form\Field\SelectBtn;
use Bs\Mvc\ControllerAdmin;
use Bs\Factory;
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
use Tk\Form\Field\InputGroup;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Uri;

class Edit extends ControllerAdmin
{
    protected ?Expense $expense = null;
    protected ?Form    $form    = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Edit Expense');
        $this->validateAccess(User::getAuthUser()?->isStaff() ?? false);

        $expenseId = intval($_GET['expenseId'] ?? 0);

        $this->expense = new Expense();
        if ($expenseId) {
            $this->expense = Expense::find($expenseId);
            if (!($this->expense instanceof Expense)) {
                throw new Exception("invalid expenseId $expenseId");
            }
        }

        // Get the form template
        $this->form = new Form();

        $this->form->appendField(new Input('description'));

        $companies = Company::findFiltered(Filter::create(['type' => Company::TYPE_SUPPLIER], 'name'));
        $list = Collection::toSelectList($companies, 'companyId');
        $this->form->appendField((new SelectBtn('companyId', $list))
            ->setLabel('Supplier')
            ->prependOption('-- Select --', '')
            ->setAttr('data-toggle', 'select2')
            ->setBtnAttr('title', 'Add Supplier')
            ->setBtnAttr('data-bs-toggle', 'modal')
            ->setBtnAttr('data-bs-target', '#'.CompanyAddDialog::CONTAINER_ID)
            ->setBtnText('<i class="fas fa-plus"></i>')
        );

        $categories = ExpenseCategory::findFiltered(Filter::create([], 'name'));
        $list = Collection::toSelectList($categories, 'expenseCategoryId');
        $this->form->appendField(new Select('expenseCategoryId', $list))
            ->prependOption('-- Select --', '')
            ->setAttr('data-toggle', 'select2');

        $this->form->appendField(new InputGroup('total', '$'));

        $this->form->appendField(new Input('purchasedOn', 'date'));

        $this->form->appendField(new Input('invoiceNo'));

        $this->form->appendField(new Input('receiptNo'));

        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', Uri::create('/expenseManager')));

        $load = $this->expense->unmapForm();
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $values = $form->getFieldValues();
        $this->expense->mapForm($values);

        $form->addFieldErrors($this->expense->validate());
        if ($form->hasErrors()) {
            return;
        }

        $isNew = ($this->expense->expenseId == 0);
        $this->expense->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create()->set('expenseId', $this->expense->expenseId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Factory::instance()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        $this->form->getField('companyId')->addFieldCss('col-6');
        $this->form->getField('expenseCategoryId')->addFieldCss('col-6');
        $this->form->getField('total')->addFieldCss('col-6');
        $this->form->getField('purchasedOn')->addFieldCss('col-6');
        $this->form->getField('invoiceNo')->addFieldCss('col-6');
        $this->form->getField('receiptNo')->addFieldCss('col-6');

        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', Factory::instance()->getBackUrl());

        if ($this->expense->expenseId) {
            $template->setVisible('edit');
            $template->setText('modified', $this->expense->modified->format(Date::FORMAT_LONG_DATETIME));
            $template->setText('created', $this->expense->created->format(Date::FORMAT_LONG_DATETIME));
        }

        $template->appendTemplate('content', $this->form->show());

        $cssCol = 'col-12';

        if ($this->expense->expenseId) {
            $url = Uri::create('/component/files', ['fkey' => $this->expense::class, 'fid' => $this->expense->expenseId]);
            $template->setAttr('files', 'hx-get', $url);
            $template->setVisible('secondary');
            $cssCol = 'col-7';
        }
        $template->addCss('primary', $cssCol);

        $companyDialogId = CompanyAddDialog::CONTAINER_ID;

        $js = <<<JS
jQuery(function($) {
    const dialog        = '#{$companyDialogId}';
    const dialogForm    = '#' + $('form', dialog).attr('id');
    const companySelect = $('#form_companyId');

    // reload select options after company creation
    $(document).on('tkForm:afterSubmit', function(e) {
        if (!$(e.detail.elt).is(dialogForm)) return;
        companySelect.empty();
        companySelect.append('<option value="">-- Select --</option>');
        for(var key in e.detail.companies) {
            companySelect.append(`<option value="\${key}">\${e.detail.companies[key]}</option>`);
        }
        // select created company
        companySelect.val(e.detail.companyId);

    });
});
JS;
        $template->appendJs($js);

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="row">
  <div class="col-12">
    <div class="page-actions card mb-3">
      <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
      <div class="card-body" var="actions">
        <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
      </div>
    </div>
  </div>

  <div var="primary">
    <div class="card mb-3">
      <div class="card-header">
        <div class="info-dropdown dropdown float-end" title="Details" choice="edit">
          <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
          <div class="dropdown-menu dropdown-menu-end">
            <p class="dropdown-item"><span class="d-inline-block">Modified:</span> <span var="modified">...</span></p>
            <p class="dropdown-item"><span class="d-inline-block">Created:</span> <span var="created">...</span></p>
          </div>
        </div>
        <i class="fas fa-money-check-alt"></i> <span var="title"></span>
      </div>
      <div class="card-body" var="content"></div>
    </div>
  </div>

  <div class="col-5" choice="secondary">
     <div hx-get="/component/files" hx-trigger="load" hx-swap="outerHTML" var="files">
       <p class="text-center mt-4"><i class="fa fa-fw fa-spin fa-spinner fa-3x"></i><br>Loading...</p>
     </div>
  </div>

  <div hx-get="/component/companyAddDialog" hx-trigger="load" hx-swap="outerHTML" var="companyAdd"></div>
</div>
HTML;
        return Template::load($html);
    }

}