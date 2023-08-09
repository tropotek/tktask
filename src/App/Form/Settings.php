<?php
namespace App\Form;

use Bs\Form\EditInterface;
use Dom\Template;
use Tk\Alert;
use Tk\Form\Field;
use Tk\Form;
use Tk\Uri;

class Settings extends EditInterface
{
    protected bool $templateSelect = false;

    public function initFields(): void
    {
        $tab = 'Site';
        $this->appendField(new Field\Input('site.name'))
            ->setLabel('Site Title')
            ->setNotes('Site Full title. Used for email subjects and content texts.')
            ->setRequired(true)
            ->setGroup($tab);

        $this->appendField(new Field\Input('site.name.short'))
            ->setLabel('Site Short Title')
            ->setNotes('Site short title. Used for nav bars and title where space is limited.')
            ->setRequired(true)
            ->setGroup($tab);

        $this->appendField(new Field\Checkbox('site.account.registration'))
            ->setLabel('Account Registration')
            ->setNotes('Enable public user registrations for this site. (Default user type is `user`)')
            //->setSwitch(true)
            ->setGroup($tab);

        if ($this->isTemplateSelect()) {
            $list = ['Side Menu' => '/html/minton/sn-admin.html', 'Top Menu' => '/html/minton/tn-admin.html'];
            $this->appendField(new Field\Select('minton.template', $list))
                ->setLabel('Template Layout')
                ->setNotes('Select Side-menu or top-menu template layout')
                ->setGroup($tab);
        }

        $tab = 'Email';
        $this->appendField(new Field\Input('site.email'))
            ->setLabel('Site Email')
            ->setRequired(true)
            ->setNotes('The default sender address when sending system emails.')
            ->setGroup($tab);

        $this->appendField(new Field\Textarea('site.email.sig'))
            ->setLabel('Email Signature')
            ->setNotes('Set the email signature to appear at the footer of all system emails.')
            ->addCss('mce-min')
            ->setGroup($tab);

        $tab = 'Metadata';
        $this->appendField(new Field\Input('system.meta.keywords'))
            ->setLabel('Metadata Keywords')
            ->setNotes('Set meta tag SEO keywords for this site. ')
            ->setGroup($tab);

        $this->appendField(new Field\Input('system.meta.description'))
            ->setLabel('Metadata Description')
            ->setNotes('Set meta tag SEO description for this site. ')
            ->setGroup($tab);

        $this->appendField(new Field\Textarea('system.global.js'))
            ->setAttr('id', 'site-global-js')
            ->setLabel('Custom JS')
            ->setNotes('You can omit the &lt;script&gt; tags here')
            ->addCss('code')->setAttr('data-mode', 'javascript')
            ->setGroup($tab);

        $this->appendField(new Field\Textarea('system.global.css'))
            ->setAttr('id', 'site-global-css')
            ->setLabel('Custom CSS Styles')
            ->setNotes('You can omit the &lt;style&gt; tags here')
            ->addCss('code')->setAttr('data-mode', 'css')
            ->setGroup($tab);

//        $tab = 'API Keys';
//        $this->appendField(new Field\Input('google.map.apikey'))
//            ->setGroup($tab)->setLabel('Google API Key')
//            ->setNotes('<a href="https://cloud.google.com/maps-platform/" target="_blank">Get Google Maps Api Key</a> And be sure to enable `Maps Javascript API`, `Maps Embed API` and `Places API for Web` for this site.')
//            ->setGroup($tab);

        $tab = 'Maintenance';
        $this->appendField(new Field\Checkbox('system.maintenance.enabled'))
            ->addCss('check-enable')
            ->setLabel('Maintenance Mode Enabled')
            ->setNotes('Enable maintenance mode. Admin users will still have access to the site.')
            ->setGroup($tab);

        $this->appendField(new Field\Textarea('system.maintenance.message'))
            ->addCss('mce-min')
            ->setLabel('Message')
            ->setNotes('Set the message public users will see when in maintenance mode.')
            ->setGroup($tab);

        $this->appendField(new Form\Action\SubmitExit('save', [$this, 'onSubmit']));
        $this->appendField(new Form\Action\Link('back', $this->getFactory()->getBackUrl()));

    }

    public function execute(array $values = []): static
    {
        $this->setFieldValues($this->getRegistry()->all());
        $values = array_combine(
            array_map(fn($r) => str_replace('_', '.', $r), array_keys($this->getRequest()->request->all()) ),
            array_values($this->getRequest()->request->all())
        );
        parent::execute($values);
        return $this;
    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action): void
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
        $renderer = $this->getFormRenderer();
        $this->getField('site.name')->addFieldCss('col-6');
        $this->getField('site.name.short')->addFieldCss('col-6');
        $renderer->addFieldCss('mb-3');
        return $renderer->show();
    }

    public function isTemplateSelect(): bool
    {
        return $this->templateSelect;
    }

    public function enableTemplateSelect(bool $templateSelect): Settings
    {
        $this->templateSelect = $templateSelect;
        return $this;
    }



}