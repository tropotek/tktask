<?php
namespace App\Controller\Expense;

use App\Db\Company;
use App\Db\Expense;
use App\Db\ExpenseCategory;
use App\Db\User;
use App\Form\Field\SelectBtn;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Form;
use Bs\Ui\Breadcrumbs;
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
        $this->getPage()->setTitle('Edit Expense', 'fas fa-money-check-alt');
        $this->validateAccess(User::getAuthUser()?->isStaff() ?? false);

        $expenseId = intval($_REQUEST['expenseId'] ?? 0);

        $this->expense = new Expense();
        if ($expenseId) {
            $this->expense = Expense::find($expenseId);
            if (is_null($this->expense)) {
                throw new Exception("invalid expenseId $expenseId");
            }
        }

        // Get the form template
        $this->form = new Form();

        $this->form->appendField(new Input('description'))
            ->setRequired();

        $companies = Company::findFiltered(Filter::create(['type' => Company::TYPE_SUPPLIER], 'name'));
        $list = Collection::toSelectList($companies, 'companyId');

        $this->form->appendField((new SelectBtn('companyId', $list))
            ->setLabel('Supplier')
            ->prependOption('-- Select --', '')
            ->setBtnAttr('title', 'Add Supplier')
            ->setBtnAttr('hx-get', Uri::create('/component/companyEditDialog'))
            ->setBtnAttr('hx-trigger', 'click queue:none')
            ->setBtnAttr('hx-target', 'body')
            ->setBtnAttr('hx-swap', 'beforeend')
            ->setBtnText('<i class="fas fa-plus"></i>')
            ->addFieldCss('col-md-6')
            ->setRequired()
        );

        $categories = ExpenseCategory::findFiltered(Filter::create([], 'name'));
        $list = Collection::toSelectList($categories, 'expenseCategoryId');
        $this->form->appendField((new Select('expenseCategoryId', $list))
            ->prependOption('-- Select --', '')
            ->addFieldCss('col-md-6')
            ->setRequired()
        );

        $this->form->appendField(new Input('invoiceNo'))
            ->setRequired();

        $this->form->appendField(new InputGroup('total', '$'))
            ->addFieldCss('col-md-6')
            ->setRequired();

        $this->form->appendField(new Input('purchasedOn', 'date'))
            ->addFieldCss('col-md-6')
            ->setRequired();

//        $this->form->appendField(new Input('receiptNo'))
//            ->addFieldCss('col-md-6')
//            ->setRequired();

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
            $action->setRedirect(Breadcrumbs::getBackUrl());
        }
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        if ($this->expense->expenseId) {
            $template->setVisible('edit');
            $template->setText('modified', $this->expense->modified->format(Date::FORMAT_LONG_DATETIME));
            $template->setText('created', $this->expense->created->format(Date::FORMAT_LONG_DATETIME));
        }

        $template->appendTemplate('content', $this->form->show());

        if ($this->expense->expenseId) {
            $url = Uri::create('/component/files', ['fkey' => Expense::class, 'fid' => $this->expense->expenseId]);
            $template->setAttr('files', 'hx-get', $url);
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
                <div class="info-dropdown dropdown float-end" title="Details" choice="edit">
                    <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <p class="dropdown-item"><span class="d-inline-block">Modified:</span> <span var="modified">...</span></p>
                        <p class="dropdown-item"><span class="d-inline-block">Created:</span> <span var="created">...</span></p>
                    </div>
                </div>
                <i var="icon"></i> <span var="title"></span>
            </div>
            <div class="card-body" var="content"></div>
        </div>
    </div>

    <div class="col-md-5" choice="components">
       <div hx-get="/component/files" hx-trigger="load" hx-swap="outerHTML" var="files">
           <p class="text-center mt-4"><i class="fa fa-fw fa-spin fa-spinner fa-3x"></i><br>Loading...</p>
       </div>
    </div>

    <script>
        jQuery(function($) {
            const companySelect = $('#form_companyId');

            // reload select options after company creation
            $(document).on('tkForm:afterSubmit', function(e) {
                if (!$(e.detail.elt).is('#form-company-edit')) return;
                companySelect.empty();
                companySelect.append('<option value="">-- Select --</option>');
                for(var key in e.detail.companies) {
                    companySelect.append(`<option value="\${key}">\${e.detail.companies[key]}</option>`);
                }
                // select created company
                companySelect.val(e.detail.companyId);
            });
        });
    </script>
</div>
HTML;
        return Template::load($html);
    }

}