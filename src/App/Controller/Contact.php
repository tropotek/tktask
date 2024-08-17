<?php
namespace App\Controller;

use Bs\ControllerDomInterface;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Action;
use Tk\Form\FormTrait;
use Tk\Traits\SystemTrait;
use Tk\Uri;

/**
 * This is only an example contact form.
 *
 * For commercial sites you should redirect to a "thank you" page or new thank you content template
 * showing a message rather than only an alert message.
 * Most clients prefer this type of
 *
 */
class Contact extends ControllerDomInterface
{
    use SystemTrait;
    use FormTrait;


    public function doDefault(Request $request): \App\Page|\Dom\Mvc\Page
    {
        $this->getPage()->setTitle('Contact Us');
        $this->setForm(Form::create('contact'));

        $hash = $_SESSION[$this->getForm()->getId() . '-nc'] ?? '';
        if (!$hash) {
            $hash = md5(time());
            $_SESSION[$this->getForm()->getId() . '-nc'] = $hash;
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

        $this->setFormRenderer(new Form\Renderer\Dom\Renderer($this->getForm()));


    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action): void
    {
        $hash = $_SESSION[$this->getForm()->getId() . '-nc'] ?? '';
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

        unset($_SESSION[$this->getForm()->getId() . '-nc']);

        $message = $this->getFactory()->createMessage();
        $message->addTo($form->getFieldValue('email'));
        $message->setSubject($this->getRegistry()->getSiteName() . ' Contact Request');
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
        $template->appendText('title', $this->getPage()->getTitle());

        $renderer = $this->getFormRenderer();
        $renderer->addFieldCss('mb-3');
        $template->appendTemplate('content', $renderer->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
<!--  <div class="card mb-3">-->
<!--    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>-->
<!--    <div class="card-body" var="actions">-->
<!--      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>-->
<!--    </div>-->
<!--  </div>-->
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-envelope"></i> </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


