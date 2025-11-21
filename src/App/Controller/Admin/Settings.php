<?php
namespace App\Controller\Admin;

use App\Db\Company;
use App\Db\Product;
use App\Db\User;
use Bs\Auth;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Form;
use Bs\Registry;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Alert;
use Tk\Collection;
use Tk\Db\Filter;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Form\Field\Textarea;
use Tk\Uri;

class Settings extends ControllerAdmin
{
    protected ?Form $form   = null;
    protected bool  $templateSelectEnabled = false;


    public function doDefault(): void
    {
        Breadcrumbs::reset();
        $this->getPage()->setTitle('Edit Settings', 'fa fa-cogs');

        $this->setUserAccess(User::PERM_SYSADMIN);

        Registry::instance()->save();

        $this->templateSelectEnabled = str_contains($this->getPage()->getTemplatePath(), '/minton/');

        $this->form = new Form();

        $tab = 'Site';
        $this->form->appendField(new Input('site.name'))
            ->setLabel('Site Title')
            ->setNotes('Site Full title. Used for email subjects and content texts.')
            ->setRequired()
            ->addFieldCss('col-md-6')
            ->setGroup($tab);

        $this->form->appendField(new Input('site.name.short'))
            ->setLabel('Site Short Title')
            ->setNotes('Site short title. Used for nav bars and title where space is limited.')
            ->setRequired()
            ->addFieldCss('col-md-6')
            ->setGroup($tab);

        $this->form->appendField(new Input('site.email'))
            ->setLabel('Site Email')
            ->setRequired()
            ->setNotes('The default sender address when sending system emails.')
            ->setGroup($tab);

        $this->form->appendField(new Textarea('site.email.sig'))
            ->setLabel('Email Signature')
            ->setNotes('Set the email signature to appear at the footer of all system emails.')
            ->addCss('mce-min')
            ->setGroup($tab);

        $tab = 'Invoicing';

        $list = Company::findFiltered(Filter::create(['active' => true], 'name'));
        $list = Collection::toSelectList($list, 'companyId');
        $this->form->appendField((new Select('site.company.id', $list))
            ->setLabel('Site Company')
            ->prependOption('-- None --', '')
            ->setRequired()
            ->setNotes('Select the owner company of this site')
            ->setGroup($tab));

        $list = Product::findFiltered(Filter::create(['active' => true, 'cycle' => Product::CYCLE_EACH], 'category_name,name'));
        $list = Collection::toSelectList($list, 'productId',
            function (Product $product) {
                return $product->categoryName . ' -> ' . $product->name . ' [' . $product->price->toString() . ']';
            }
        );
        $this->form->appendField((new Select('site.product.labor.default', $list))
            ->setLabel('Default Labour Product')
            ->prependOption('-- None --', '')
            ->setRequired()
            ->setNotes('Select the default product to use when completing tasks')
            ->setGroup($tab));

        $list = ['' => '-- System Default --', '1' => 'Yes', '0' => 'No'];
        $this->form->appendField(new Select('site.taskLog.billable.default', $list))
            ->setLabel('Log Default Billable')
            ->setNotes('Set the default billable status when creating Task Logs. None means force the user to choose.')
            ->setGroup($tab);

        $list = ['14' => '14 Days', '28' => '28 Days', '30' => '30 Days', '60' => '60 Days', '90' => '90 Days'];
        $this->form->appendField(new Select('site.account.overdue.days', $list))
            ->setLabel('Invoice Payment Due Days')
            ->setNotes('After this number of days the invoice is flagged as payment overdue.')
            ->setGroup($tab);

        $this->form->appendField((new Checkbox('site.invoice.enable', ['1' => 'Enable task invoicing, products and recurring invoicing systems.']))
            ->setLabel('')
            ->setSwitch(true)
            ->setGroup($tab));

        $this->form->appendField((new Checkbox('site.expenses.enable', ['1' => 'Enable expenses and profit reporting.']))
            ->setLabel('')
            ->setSwitch(true)
            ->setGroup($tab));

        $this->form->appendField(new Textarea('site.invoice.payment'))
            ->setLabel('Payment Methods')
            ->setNotes('Enter the footer method text to be placed at the invoice footer.')
            ->addCss('mce')
            ->setGroup($tab);

        $tab = 'Page Content';

        if ($this->templateSelectEnabled) {
            $list = ['' => '-- Default --', 'sn-admin' => 'Side Menu', 'tn-admin' => 'Top Menu'];
            $this->form->appendField(new \Tk\Form\Field\Select('minton.template', $list))
                ->setLabel('Template Layout')
                ->setNotes('Select a side-menu or top-menu template as the default site layout. Users can customise their own selection.')
                ->setGroup($tab);
        }

        $this->form->appendField(new Input('system.meta.keywords'))
            ->setLabel('Metadata Keywords')
            ->setNotes('Set meta tag SEO keywords for this site. ')
            ->setGroup($tab);

        $this->form->appendField(new Input('system.meta.description'))
            ->setLabel('Metadata Description')
            ->setNotes('Set meta tag SEO description for this site. ')
            ->setGroup($tab);

        $this->form->appendField(new Textarea('system.global.js'))
            ->setAttr('id', 'site-global-js')
            ->setLabel('Custom JS')
            ->setNotes('You can omit the &lt;script&gt; tags here')
            ->addCss('code')->setAttr('data-mode', 'javascript')
            ->setGroup($tab);

        $this->form->appendField(new Textarea('system.global.css'))
            ->setAttr('id', 'site-global-css')
            ->setLabel('Custom CSS Styles')
            ->setNotes('You can omit the &lt;style&gt; tags here')
            ->addCss('code')->setAttr('data-mode', 'css')
            ->setGroup($tab);

        $tab = 'Maintenance';
        $this->form->appendField((new Checkbox('system.maintenance.enabled', ['1' => 'Maintenance Mode Enabled']))
            ->addCss('check-enable')
            ->setSwitch(true)
            ->setLabel('')
            ->setNotes('Enable maintenance mode. Admin users will still have access to the site.')
            ->setGroup($tab)
        );

        $this->form->appendField(new Textarea('system.maintenance.message'))
            ->addCss('mce-min')
            ->setLabel('Maintenance Message')
            ->setNotes('Set the message public users will see when in maintenance mode.')
            ->setGroup($tab);


        // Form Actions
        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('back', Breadcrumbs::getBackUrl()));

        // Load form with object values
        $this->form->setFieldValues(Registry::instance()->all());

        // Execute form with request values
        $values = array_combine(
            array_map(fn($r) => str_replace('_', '.', $r), array_keys($_POST) ),
            array_values($_POST)
        ) + $_POST; // keep the original post values for the events

        $this->form->execute($values);
    }

    public function onSubmit(Form $form, SubmitExit $action): void
    {
        // ignore any fields starting with underscores
        $values = $form->getFieldValues('/^[^_]/');
        Registry::instance()->replace($values);

        if (strlen($values['site.name'] ?? '') < 3) {
            $form->addFieldError('site.name', 'Please enter your name');
        }
        if (!filter_var($values['site.email'] ?? '', \FILTER_VALIDATE_EMAIL)) {
            $form->addFieldError('site.email', 'Please enter a valid email address');
        }
        if (empty($values['site.company.id'] ?? '')) {
            $form->addFieldError('site.company.id', 'Please select a valid owner company');
        }

        if ($form->hasErrors()) return;

        Registry::instance()->save();

        Alert::addSuccess('Site settings saved successfully.');
        $action->setRedirect(Uri::create());
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Breadcrumbs::getBackUrl());
        }
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        $template->setVisible('staff', Auth::getAuthUser()->hasPermission(User::PERM_SYSADMIN));
        $template->setVisible('admin', Auth::getAuthUser()->hasPermission(User::PERM_ADMIN));

        $this->form->getRenderer()->addFieldCss('mb-3');
        $template->appendTemplate('content', $this->form->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-body" var="actions">
      <a href="/sessions" title="View Active Sessions" class="btn btn-outline-secondary" choice="admin"><i class="fa fa-fw fa-server"></i> Sessions</a>
      <a href="/user/staffManager" title="Manage Staff" class="btn btn-outline-secondary" choice="staff"><i class="fa fa-fw fa-users"></i> Staff</a>
      <a href="/companyManager" title="Manage Companies" class="btn btn-outline-secondary"><i class="fa fa-fw fa-building"></i> Companies</a>
      <a href="/taskCategoryManager" title="Manage Task Categories" class="btn btn-outline-secondary"><i class="fa fa-fw fa-folder-open"></i> Task Categories</a>
      <a href="/productCategoryManager" title="Manage Product Categories" class="btn btn-outline-secondary"><i class="fa fa-fw fa-folder-open"></i> Product Categories</a>
      <a href="/productManager" title="Manage Products" class="btn btn-outline-secondary"><i class="fa fa-fw fa-shopping-cart"></i> Products</a>
      <a href="/expenseCategoryManager" title="Manage Expense Categories" class="btn btn-outline-secondary"><i class="fa fa-fw fa-folder-open"></i> Expense Categories</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i var="icon"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}