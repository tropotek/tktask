<?php
namespace App\Form;

use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Form\Field;
use Tk\Form;
use Tk\FormRenderer;
use Tk\Traits\SystemTrait;
use Tk\Uri;

class Settings
{
    use SystemTrait;
    use Form\FormTrait;


    public function __construct()
    {
        $this->setForm(Form::create('settings-edit'));
        $this->setFormRenderer(new FormRenderer($this->getForm()));
    }

    public function doDefault(Request $request)
    {
        $tab = 'Site';
        $this->getForm()->appendField(new Field\Input('system.site.name'))->setGroup($tab)->setLabel('Site Title')->setRequired(true);
        $this->getForm()->appendField(new Field\Input('system.site.shortName'))->setGroup($tab)->setLabel('Site Short Title')->setRequired(true);
        $this->getForm()->appendField(new Field\Input('system.email'))->setGroup($tab)->setLabel('Site Email')->setRequired(true)
            ->setNotes('The default email address the system will use to send contact requests and system messages.');

        $this->getForm()->appendField(new Field\Input('google.map.apikey'))->setGroup($tab)->setLabel('Google API Key')
            ->setNotes('<a href="https://cloud.google.com/maps-platform/" target="_blank">Get Google Maps Api Key</a> And be sure to enable `Maps Javascript API`, `Maps Embed API` and `Places API for Web` for this site.');
        $this->getForm()->appendField(new Field\Checkbox('site.account.registration'))->setGroup($tab)->setLabel('Account Registration');
//        $this->getForm()->appendField(new Field\Checkbox('site.account.activation'))->setGroup($tab)->setLabel('Account Activation')
//            ->setNotes('If not checked admin manually activate new user accounts.');

        $tab = 'Email';
        $this->getForm()->appendField(new Field\Textarea('site.email.sig'))->setGroup($tab)->setLabel('Email Signature')
            ->setNotes('Set the email signature to appear at the foot of all system emails.')->addCss('mce-min');

        $tab = 'Metadata';
        $this->getForm()->appendField(new Field\Input('system.meta.keywords'))->setGroup($tab)->setLabel('Metadata Keywords');
        $this->getForm()->appendField(new Field\Input('system.meta.description'))->setGroup($tab)->setLabel('Metadata Description');

        $this->getForm()->appendField(new Field\Textarea('system.global.js'))->setAttr('id', 'site-global-js')->setGroup($tab)->setLabel('Custom JS')
            ->setNotes('You can omit the &lt;script&gt; tags here')->addCss('code')->setAttr('data-mode', 'javascript');
        $this->getForm()->appendField(new Field\Textarea('system.global.css'))->setAttr('id', 'site-global-css')->setGroup($tab)->setLabel('Custom CSS Styles')
            ->setNotes('You can omit the &lt;style&gt; tags here')->addCss('code')->setAttr('data-mode', 'css');

        $tab = 'Maintenance';
        $this->getForm()->appendField(new Field\Checkbox('system.maintenance.enabled'))->addCss('check-enable')->setLabel('Maintenance Mode Enabled')->setGroup($tab);
        $this->getForm()->appendField(new Field\Textarea('system.maintenance.message'))->addCss('mce-min')->setGroup($tab)->setLabel('Message');


        $this->getForm()->appendField(new Form\Action\Link('back', Uri::create('/')));
        $this->getForm()->appendField(new Form\Action\Submit('save', [$this, 'onSubmit']));
//        $this->getForm()->appendField(new Event\Submit('update', array($this, 'onSubmit')));
//        $this->getForm()->appendField(new Event\Submit('save', array($this, 'onSubmit')));
//        $this->getForm()->appendField(new Event\LinkButton('cancel', $this->getBackUrl()));

        $this->getForm()->setFieldValues($this->getRegistry()->all()); // Use form data mapper if loading objects

        // Replace converted key from request
        $values = array_combine(
            array_map(fn($r) => str_replace('_', '.', $r), array_keys($this->getRequest()->request->all()) ),
            array_values($this->getRequest()->request->all())
        );
        $this->getForm()->execute($values);

        //return $this->show();
    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action)
    {
        $values = $form->getFieldValues();
        $this->getRegistry()->replace($values);

        if (strlen($values['system.site.name'] ?? '') < 3) {
            $form->addFieldError('system.site.name', 'Please enter your name');
        }
        if (!filter_var($values['system.email'] ?? '', \FILTER_VALIDATE_EMAIL)) {
            $form->addFieldError('system.email', 'Please enter a valid email address');
        }

        if ($form->hasErrors()) return;

        $this->getRegistry()->save();

        $form->getSession()->getFlashBag()->add('success', 'Site settings saved successfully.');
        $action->setRedirect(Uri::create());
    }

    public function show(): ?Template
    {
        return $this->getFormRenderer()->show();
    }
}