<?php
namespace App\Form;

use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Encrypt;
use Tk\Form;
use Tk\FormRenderer;
use Tk\Form\Field;
use Tk\Form\Action;
use Tk\Traits\SystemTrait;
use Tk\Uri;

class Register
{
    use SystemTrait;
    use Form\FormTrait;

    protected ?\App\Db\User $user = null;

    public function __construct()
    {
        // Set a token in the session on show, to ensure this browser is the one that requested the login.
        $this->getSession()->set('recover', time());
        $this->setForm(Form::create('register'));
    }

    public function doDefault(Request $request)
    {
        $this->form->appendField(new Field\Input('name'))
            ->setRequired()
            ->setAttr('placeholder', 'Name');

        $this->form->appendField(new Field\Input('email'))
            ->setRequired()
            ->setAttr('placeholder', 'Email');

        $this->form->appendField(new Field\Input('username'))
            ->setAttr('placeholder', 'Username')
            ->setAttr('autocomplete', 'off')
            ->setRequired();

        $this->form->appendField(new Field\Password('password'))
            ->setAttr('placeholder', 'Password')
            ->setAttr('autocomplete', 'off')
            ->setRequired();

        $this->form->appendField(new Field\Password('confPassword'))
            ->setLabel('Password Confirm')
            ->setAttr('placeholder', 'Password Confirm')
            ->setAttr('autocomplete', 'off')
            ->setRequired();

        $html = <<<HTML
            <a href="/recover">Recover</a> | <a href="/login">Login</a>
        HTML;
        $this->getForm()->appendField(new Field\Html('links', $html))->setLabel('')->addFieldCss('text-center');
        $this->getForm()->appendField(new Action\Submit('register', [$this, 'onSubmit']));

        $load = [];
        $this->getForm()->setFieldValues($load);

        $this->getForm()->execute($request->request->all());

        $this->setFormRenderer(new FormRenderer($this->getForm()));
    }

    public function onSubmit(Form $form, Action\ActionInterface $action)
    {
        if (!$this->getRegistry()->get('site.account.registration', false)) {
            Alert::addError('New user registrations are closed for this account');
            Uri::create('/home')->redirect();
        }

        $this->user = new \App\Db\User();
        $this->user->setActive(false);
        $this->user->setNotes('pending activation');
        $this->user->setType(\App\Db\User::TYPE_USER);

        $this->user->getMapper()->getFormMap()->loadObject($this->user, $form->getFieldValues());

        $token = $this->getSession()->get('recover', 0);
        $this->getSession()->remove('recover');
        if (($token + 60*2) < time()) { // submit before form token times out
            $form->addError('Invalid form submission, please try again.');
            return;
        }

        if (!$form->getFieldValue('password')  || $form->getFieldValue('password') != $form->getFieldValue('confPassword')) {
            $form->addFieldError('password');
            $form->addFieldError('confPassword');
            $form->addFieldError('confPassword', 'Passwords do not match');
        } else {
            if (!$this->getConfig()->isDebug()) {
                $errors = \App\Db\User::checkPassword($form->getFieldValue('password'));
                if (count($errors)) {
                    $form->addFieldError('confPassword', implode('<br/>', $errors));
                }
            }
        }

        $form->addFieldErrors($this->user->validate());

        if ($form->hasErrors()) {
            return;
        }

        $this->user->setPassword(\App\Db\User::hashPassword($this->user->getPassword()));
        $this->user->save();

        // send email to user
        $content = <<<HTML
            <h2>Account Activation.</h2>
            <p>
              Welcome {name}
            </p>
            <p>
              Please follow the link to activate your account and finish the user registration.<br/>
              <a href="{activate-url}" target="_blank">{activate-url}</a>
            </p>
            <p><small>Note: If you did not initiate this account creation you can safely disregard this message.</small></p>
        HTML;

        $message = $this->getFactory()->createMessage();
        $message->set('content', $content);
        $message->setSubject($this->getRegistry()->get('system.site.name') . ' Account Registration');
        $message->addTo($this->user->getEmail());
        $message->set('name', $this->user->getName());

        $hashToken = Encrypt::create($this->getConfig()->get('system.encrypt'))->encrypt(serialize(['h' => $this->user->getHash(), 't' => time()]));
        $url = Uri::create('/registerActivate')->set('t', $hashToken);
        $message->set('activate-url', $url->toString());

        $this->getFactory()->getMailGateway()->send($message);

        Alert::addSuccess('Please check your email for instructions to activate your account.');
        Uri::create('/home')->redirect();
    }

    public function show(): ?Template
    {
        return $this->getFormRenderer()->show();
    }

}