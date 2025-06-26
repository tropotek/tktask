<?php
namespace App\Controller\User;

use Bs\Auth;
use Bs\Mvc\ControllerDomInterface;
use App\Db\User;
use Bs\Db\GuestToken;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Config;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Html;
use Tk\Form\Field\Input;
use Tk\Form\Field\Password;
use Tk\Uri;

class Register extends ControllerDomInterface
{

    protected ?Form $form  = null;
    protected ?Auth $auth  = null;
    protected bool  $isReg = true;
    protected ?GuestToken $token = null;


    public function __construct()
    {
        $this->setPageTemplate(Config::getValue('path.template.login'));
    }

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Register');

        if (!Config::getValue('auth.registration.enable', false)) {
            Alert::addError('User registrations are closed for this account');
            Uri::create('/')->redirect();
        }

        $this->form = new Form();

        $this->form->appendField(new Input('name'))
            ->setRequired()
            ->setAttr('placeholder', 'Name');

        $this->form->appendField(new Input('email'))
            ->setRequired()
            ->setType('email')
            ->setAttr('placeholder', 'Email');

        $this->form->appendField(new Input('username'))
            ->setAttr('placeholder', 'Username')
            ->setAttr('autocomplete', 'off')
            ->setRequired();

        $html = <<<HTML
            <a href="/recover">Recover</a> | <a href="/login">Login</a>
        HTML;
        $this->form->appendField(new Html('links', $html))->setLabel('')->addFieldCss('text-center');
        $this->form->appendField(new Submit('register', [$this, 'onSubmit']));

        $load = [];
        $this->form->setFieldValues($load);
        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, Submit $action): void
    {
        if (!Config::getValue('auth.registration.enable', false)) {
            Alert::addError('New user registrations are closed for this account');
            Uri::create('/')->redirect();
        }

        $user = new User();
        $user->type = User::TYPE_STAFF;

        // set object values from fields
        $user->mapForm($form->getFieldValues());

        if (!$form->getFieldValue('name')) {
            $form->addFieldError('name', 'Please enter a valid name');
        }
        if (!filter_var($form->getFieldValue('email'), FILTER_VALIDATE_EMAIL)) {
            $form->addFieldError('email', 'Please enter a valid email');
        }

        if (!$form->getFieldValue('username')) {
            $form->addFieldError('username', 'Invalid field username value');
        } else {
            $dup = Auth::findByUsername($form->getFieldValue('username'));
            if ($dup instanceof Auth) {
                $form->addFieldError('username', 'This username is unavailable');
            }
        }

        if (!filter_var($form->getFieldValue('email'), FILTER_VALIDATE_EMAIL)) {
            $form->addFieldError('email', 'Please enter a valid email address');
        } else {
            $dup = Auth::findByEmail($form->getFieldValue('email'));
            if ($dup instanceof Auth) {
                $form->addFieldError('email', 'This email is unavailable');
            }
        }

        $form->addFieldErrors($user->validate());
        if ($form->hasErrors()) {
            return;
        }

        [$user->givenName, $user->familyName] = explode(' ', $form->getFieldValue('name'));
        $user->save();

        $auth = $user->getAuth();
        $user->mapForm($form->getFieldValues());
        $auth->active = false;
        $auth->save();

        // reload user
        $user->reload();

        \App\Email\User::sendRegister($user);

        Alert::addSuccess('Please check your email for instructions to activate your account.');
        Uri::create('/')->redirect();
    }

    /**
     *
     */
    public function doActivate(): void
    {
        $this->getPage()->setTitle('Activate Account');
        $this->isReg = false;

        if (!Config::getValue('auth.registration.enable', false)) {
            Alert::addError('New user registrations are closed for this account');
            Uri::create('/')->redirect();
        }

        // logout any existing user
        Auth::logout();

        $this->token = GuestToken::getSessionToken();
        if (is_null($this->token)) {
            Alert::addError('You do not have permission to access this page.');
            Uri::create('/')->redirect();
        }

        $this->auth = Auth::findByHash($this->token->payload['h'] ?? '');
        if (is_null($this->auth) || $this->auth->active) {
            Alert::addError('Invalid user token');
            Uri::create('/')->redirect();
        }

        $this->form = new Form();

        $this->form->appendField(new Password('password'))
            ->setAttr('placeholder', 'Password')
            ->setAttr('autocomplete', 'off')
            ->setRequired();

        $this->form->appendField(new Password('confPassword'))
            ->setLabel('Password Confirm')
            ->setAttr('placeholder', 'Password Confirm')
            ->setAttr('autocomplete', 'off')
            ->setRequired();

        $html = <<<HTML
            <a href="/login">Login</a>
        HTML;
        $this->form->appendField(new Html('links', $html))->setLabel('')->addFieldCss('text-center');
        $this->form->appendField(new Submit('activate', [$this, 'onActivate']));

        $load = [];
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

    }

    public function onActivate(Form $form, Submit $action): void
    {
        if (!Config::getValue('auth.registration.enable', false)) {
            Alert::addError('New user registrations are closed for this account');
            Uri::create('/')->redirect();
        }

        if (!$form->getFieldValue('password')  || $form->getFieldValue('password') != $form->getFieldValue('confPassword')) {
            $form->addFieldError('password', 'Invalid password');
            $form->addFieldError('confPassword', 'Passwords do not match');
        } else {
            $errors = Auth::validatePassword($form->getFieldValue('password'));
            if (count($errors)) {
                $form->addFieldError('confPassword', implode('<br/>', $errors));
            }
        }

        $form->addFieldErrors($this->auth->validate());
        if ($form->hasErrors()) {
            return;
        }

        $this->auth->password = Auth::hashPassword($form->getFieldValue('password'));
        $this->auth->active = true;
        $this->auth->save();

        $this->token->delete();

        /** @var User $user */
        $user = $this->auth->getDbModel();
        if ($user) {
            \App\Email\User::sendWelcome($user);
        }

        Alert::addSuccess('You account has been successfully activated, please login.');
        Uri::create('/login')->redirect();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        if (!$this->isReg) {
            $template->setText('title', 'Account Activation');
            $template->setVisible('activate');
        }

        if ($this->form) {
            $template->appendTemplate('content', $this->form->show());
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
    <h1 class="h3 mb-3 fw-normal text-center" var="title">Account Registration</h1>
    <p choice="activate">Set your new account password</p>
    <div class="" var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}