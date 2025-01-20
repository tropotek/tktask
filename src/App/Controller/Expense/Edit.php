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
    protected ?Files   $files   = null;

    protected ?CompanyAddDialog $companyDialog = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Edit Expense');

        if (!User::getAuthUser()?->isStaff()) {
            Alert::addWarning('You do not have permission to access this page');
            User::getAuthUser()?->getHomeUrl()->redirect() ?? Uri::create('/')->redirect();
        }

        $expenseId = intval($_GET['expenseId'] ?? 0);

        $this->expense = new Expense();
        if ($expenseId) {
            $this->expense = Expense::find($expenseId);
            if (!($this->expense instanceof Expense)) {
                throw new Exception("invalid expenseId $expenseId");
            }
        }

        $this->companyDialog = new CompanyAddDialog();


        // Get the form template
        $this->form = new Form();

        $this->form->appendField(new Input('description'));

        $companies = Company::findFiltered(Filter::create(['type' => Company::TYPE_SUPPLIER], 'name'));
        $list = Collection::toSelectList($companies, 'companyId');
        $this->form->appendField((new SelectBtn('companyId', $list))
            ->prependOption('-- Select --', '')
            ->setBtnAttr('title', 'Add Supplier')
            ->setBtnAttr('data-bs-toggle', 'modal')
            ->setBtnAttr('data-bs-target', '#'.$this->companyDialog->getDialogId())
            ->setBtnText('<i class="fas fa-plus"></i>')
        );

        $categories = ExpenseCategory::findFiltered(Filter::create([], 'name'));
        $list = Collection::toSelectList($categories, 'expenseCategoryId');
        $this->form->appendField(new Select('categoryId', $list))->prependOption('-- Select --', '');

        $this->form->appendField(new InputGroup('total', '$'));

        $this->form->appendField(new Input('purchasedOn', 'date'));

        $this->form->appendField(new Input('invoiceNo'));

        $this->form->appendField(new Input('receiptNo'));

        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', Uri::create('/expenseManager')));

        $load = $this->form->unmapModel($this->expense);
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

        if ($this->expense->expenseId) {
            $this->files = new Files($this->expense);
        }

    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $form->mapModel($this->expense);

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
        $this->form->getField('categoryId')->addFieldCss('col-6');
        $this->form->getField('total')->addFieldCss('col-6');
        $this->form->getField('purchasedOn')->addFieldCss('col-6');
        $this->form->getField('invoiceNo')->addFieldCss('col-6');
        $this->form->getField('receiptNo')->addFieldCss('col-6');

        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', Factory::instance()->getBackUrl());

        $template->appendTemplate('content', $this->form->show());

        $cssCol = 'col-12';
        if ($this->files) {
            $html = $this->files->doDefault();
            $template->appendHtml('secondary', $html);
            $template->setVisible('secondary');
            $cssCol = 'col-7';
        }
        $template->addCss('primary', $cssCol);

        if ($this->companyDialog) {
            $template->appendTemplate('primary', $this->companyDialog->doDefault());

            $js = <<<JS
jQuery(function($) {
    const dialog        = '#{$this->companyDialog->getDialogId()}';
    const dialogForm    = $('form', dialog);
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
        }

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
      <div class="card-header"><i class="fas fa-money-check-alt"></i> <span var="title"></span></div>
      <div class="card-body" var="content"></div>
    </div>
  </div>
  <div class="col-5" choice="secondary"></div>
</div>
HTML;
        return Template::load($html);
    }

}