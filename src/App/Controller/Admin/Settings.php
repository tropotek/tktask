<?php
namespace App\Controller\Admin;

use Bs\ControllerAdmin;
use Bs\Db\Permissions;
use Bs\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Input;
use Tk\Form\Field\Textarea;
use Tk\Ui\Button;
use Tk\Uri;

class Settings extends ControllerAdmin
{
    protected ?Form $form   = null;
    protected bool  $templateSelect = false;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Edit Settings');

        $this->setAccess(Permissions::PERM_SYSADMIN);

        $this->getRegistry()->save();
        $this->getCrumbs()->reset();

        $this->templateSelect = str_contains($this->getPage()->getTemplatePath(), '/minton/');

        $this->form = new Form();

        $tab = 'Site';
        $this->form->appendField(new Input('site.name'))
            ->setLabel('Site Title')
            ->setNotes('Site Full title. Used for email subjects and content texts.')
            ->setRequired(true)
            ->setGroup($tab);

        $this->form->appendField(new Input('site.name.short'))
            ->setLabel('Site Short Title')
            ->setNotes('Site short title. Used for nav bars and title where space is limited.')
            ->setRequired(true)
            ->setGroup($tab);

        $this->form->appendField(new Checkbox('site.account.registration'))
            ->setLabel('Account Registration')
            ->setNotes('Enable public user registrations for this site. (Default user type is `user`)')
            //->setSwitch(true)
            ->setGroup($tab);

        if ($this->templateSelect) {
            $list = ['Side Menu' => '/html/minton/sn-admin.html', 'Top Menu' => '/html/minton/tn-admin.html'];
            $this->form->appendField(new \Tk\Form\Field\Select('minton.template', $list))
                ->setLabel('Template Layout')
                ->setNotes('Select Side-menu or top-menu template layout')
                ->setGroup($tab);
        }


        $tab = 'Email';
        $this->form->appendField(new Input('site.email'))
            ->setLabel('Site Email')
            ->setRequired(true)
            ->setNotes('The default sender address when sending system emails.')
            ->setGroup($tab);

        $this->form->appendField(new Textarea('site.email.sig'))
            ->setLabel('Email Signature')
            ->setNotes('Set the email signature to appear at the footer of all system emails.')
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


//        $tab = 'API Keys';
//        $this->form->appendField(new Input('google.map.apikey'))
//            ->setGroup($tab)->setLabel('Google API Key')
//            ->setNotes('<a href="https://cloud.google.com/maps-platform/" target="_blank">Get Google Maps Api Key</a> And be sure to enable `Maps Javascript API`, `Maps Embed API` and `Places API for Web` for this site.')
//            ->setGroup($tab);


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
        $this->form->appendField(new Link('back', $this->getFactory()->getBackUrl()));

        // Load form with object values
        $this->form->setFieldValues($this->getRegistry()->all());

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
        $this->getRegistry()->replace($values);

        if (strlen($values['site.name'] ?? '') < 3) {
            $form->addFieldError('site.name', 'Please enter your name');
        }
        if (!filter_var($values['site.email'] ?? '', \FILTER_VALIDATE_EMAIL)) {
            $form->addFieldError('site.email', 'Please enter a valid email address');
        }

        if ($form->hasErrors()) return;

        $this->getRegistry()->save();

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

        $template->setVisible('staff', $this->getAuthUser()->hasPermission(Permissions::PERM_MANAGE_STAFF));
        $template->setVisible('member', $this->getAuthUser()->hasPermission(Permissions::PERM_MANAGE_MEMBERS));

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
      <a href="/user/staffManager" title="Manage Staff" class="btn btn-outline-secondary"><i class="fa fa-fw fa-users" choice="staff"></i> Staff</a>
      <a href="/user/memberManager" title="Manage Members" class="btn btn-outline-secondary"><i class="fa fa-fw fa-users" choice="member"></i> Members</a>
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