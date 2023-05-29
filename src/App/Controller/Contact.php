<?php
namespace App\Controller;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Action;
use Tk\Form\FormTrait;
use Tk\FormRenderer;
use Tk\Traits\SystemTrait;
use Tk\Uri;

class Contact extends PageController
{
    use SystemTrait;
    use FormTrait;

    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('Contact Us');
        $this->setForm(Form::create('contact'));
    }

    public function doDefault(Request $request)
    {
        $hash = $this->getSession()->get($this->getForm()->getId() . '-nc');
        if (!$hash) {
            $hash = md5(time());
            $this->getSession()->set($this->getForm()->getId() . '-nc', $hash);
        }
        $this->getForm()->appendField(new Field\Hidden('nc'))->setValue($hash);
        $this->getForm()->appendField(new Field\Input('name'));
        $this->getForm()->appendField(new Field\Input('email'))->setType('email');
        $this->getForm()->appendField(new Field\Input('phone'));
        $this->getForm()->appendField(new Field\Textarea('message'));


        $this->getForm()->appendField(new Action\Submit('send', [$this, 'onSubmit']));
        $this->getForm()->appendField(new Action\Link('cancel', Uri::create()));

        $this->getForm()->setFieldValues($this->getRegistry()->all()); // Use form data mapper if loading objects

        $this->getForm()->execute($request->request->all());

        $this->setFormRenderer(new FormRenderer($this->getForm()));


        return $this->getPage();
    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action)
    {
        $hash = $this->getSession()->get($this->getForm()->getId() . '-nc');
        if ($form->getFieldValue('nc') != $hash) {
            $form->addError('Form system error, please try again.');
        }
        if (!$form->getFieldValue('name')) {
            $form->addFieldError('name', 'Please enter a valid name.');
        }
        if (!filter_var($form->getFieldValue('email'), FILTER_VALIDATE_EMAIL)) {
            $form->addFieldError('email', 'Please enter a valid email.');
        }
        if (!$form->getFieldValue('message')) {
            $form->addFieldError('message', 'Please enter a valid message.');
        }

        if ($form->hasErrors()) return;
        $this->getSession()->remove($this->getForm()->getId() . '-nc');

        $message = $this->getFactory()->createMessage();
        $message->addTo($form->getFieldValue('email'));
        $message->setSubject($this->getRegistry()->get('system.site.name') . ' Contact Request');
        $content = <<<HTML
<p>
Dear {name},
</p>
<p>
Email: {email}<br/>
Phone: {phone}<br/>
</p>
<p>Message:<br/>
  {message}
</p>
HTML;
        $message->setContent($content);
        $message->replace($form->getFieldValues());
        $this->getFactory()->getMailGateway()->send($message);

        Alert::addSuccess('Message Sent successfully');
        $action->setRedirect(Uri::create());
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        $renderer = $this->getFormRenderer();
        $renderer->addFieldCss('mb-3');
        $template->appendTemplate('content', $renderer->show());

        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>

    <div class="card">
        <h3 class="card-header" var="title">Contact Us</h3>
        <div class="card-body row">
            <div class="col-6">
                <p>This is the left side</p>
                <p>Location</p>
                <div>{Map Here!}</div>
            </div>
            <div class="col-6">
                <div var="content"></div>
            </div>
        </div>
    </div>

</div>
HTML;
        return $this->loadTemplate($html);
    }

}


