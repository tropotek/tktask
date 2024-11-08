<?php
namespace App\Controller\Admin;

use App\Db\Company;
use App\Db\User;
use App\Factory;
use Bs\Auth;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Form;
use Bs\Registry;
use Dom\Template;
use Tk\Alert;
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
    protected bool  $templateSelect = false;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Edit Settings');

        $this->setAccess(User::PERM_SYSADMIN);

        Factory::instance()->getRegistry()->save();
        $this->getCrumbs()->reset();

        $this->templateSelect = str_contains($this->getPage()->getTemplatePath(), '/minton/');

        $this->form = new Form();

        $tab = 'Site';
        $this->form->appendField(new Input('site.name'))
            ->setLabel('Site Title')
            ->setNotes('Site Full title. Used for email subjects and content texts.')
            ->setRequired()
            ->setGroup($tab);

        $this->form->appendField(new Input('site.name.short'))
            ->setLabel('Site Short Title')
            ->setNotes('Site short title. Used for nav bars and title where space is limited.')
            ->setRequired()
            ->setGroup($tab);

        if ($this->templateSelect) {
            $list = ['Side Menu' => '/html/minton/sn-admin.html', 'Top Menu' => '/html/minton/tn-admin.html'];
            $this->form->appendField(new Select('minton.template', $list))
                ->setLabel('Template Layout')
                ->setNotes('Select Side-menu or top-menu template layout')
                ->setGroup($tab);
        }

        $this->form->appendField(new Input('site.email'))
            ->setLabel('Site Email')
            ->setRequired()
            ->setNotes('The default sender address when sending system emails.')
            ->setGroup($tab);

        $list = Company::findFiltered(Filter::create(['active' => true], 'name'));
        $this->form->appendField((new Select('site.company.id', $list))
            ->setLabel('Site Company')
            ->prependOption('-- None --', '')
            ->setRequired()
            ->setNotes('Select the owner company of this site')
            ->setGroup($tab));

        $list = array('-- System Default --' => '', 'Yes' => '1', 'No' => '0');
        $this->form->appendField(new Select('site.taskLog.billable.default', $list))
            ->setLabel('Log Default Billable')
            ->setNotes('Set the default billable status when creating Task Logs. None means force the user to choose.')
            ->setGroup($tab);

        $this->form->appendField(new Textarea('site.email.sig'))
            ->setLabel('Email Signature')
            ->setNotes('Set the email signature to appear at the footer of all system emails.')
            ->addCss('mce-min')
            ->setGroup($tab);

        $tab = 'Setup';
        $this->form->appendField((new Checkbox('site.invoice.enable', ['Enable task invoicing, products and recurring invoicing systems.' => '1']))
            ->setLabel('Enable Invoicing')
            ->setGroup($tab));

        $this->form->appendField((new Checkbox('site.expenses.enable', ['Enable expenses and profit reporting.' => '1']))
            ->setLabel('Enable Expenses')
            ->setGroup($tab));

        $this->form->appendField(new Textarea('site.invoice.payment'))
            ->setLabel('Payment Methods')
            ->setNotes('Enter the footer method text to be placed at the invoice footer.')
            ->addCss('mce-min')
            ->setGroup($tab);

        $tab = 'Metadata';
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
        $this->form->appendField(new Checkbox('system.maintenance.enabled'))
            ->addCss('check-enable')
            ->setLabel('Maintenance Mode Enabled')
            ->setNotes('Enable maintenance mode. Admin users will still have access to the site.')
            ->setGroup($tab);

        $this->form->appendField(new Textarea('system.maintenance.message'))
            ->addCss('mce-min')
            ->setLabel('Message')
            ->setNotes('Set the message public users will see when in maintenance mode.')
            ->setGroup($tab);


        // Form Actions
        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('back', \Bs\Factory::instance()->getBackUrl()));

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
        $values = $form->getFieldValues();
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
            $action->setRedirect($this->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->setVisible('staff', Auth::getAuthUser()->hasPermission(User::PERM_MANAGE_STAFF));
        $template->setVisible('member', Auth::getAuthUser()->hasPermission(User::PERM_MANAGE_MEMBERS));
        $template->setVisible('admin', Auth::getAuthUser()->hasPermission(User::PERM_ADMIN));

        $this->form->getField('site.name')->addFieldCss('col-6');
        $this->form->getField('site.name.short')->addFieldCss('col-6');
        $this->form->getRenderer()->addFieldCss('mb-3');

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
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-fw fa-arrow-left"></i> Back</a>
      <a href="/sessions" title="View Active Sessions" class="btn btn-outline-secondary" choice="admin"><i class="fa fa-fw fa-server"></i> Sessions</a>
      <a href="/user/staffManager" title="Manage Staff" class="btn btn-outline-secondary" choice="staff"><i class="fa fa-fw fa-users"></i> Staff</a>
<!--      <a href="/user/memberManager" title="Manage Members" class="btn btn-outline-secondary" choice="member"><i class="fa fa-fw fa-users"></i> Members</a>-->
      <a href="/companyManager" title="Manage Companies" class="btn btn-outline-secondary"><i class="fa fa-fw fa-building"></i> Companies</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-cogs"></i> </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}