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
use Tk\Exception;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Html;
use Tk\Form\Field\Input;
use Tk\Form\Field\Password;
use Tk\Uri;

class Recover extends ControllerDomInterface
{
    protected ?Form $form = null;
    protected ?Auth $auth = null;
    protected ?GuestToken $token = null;

    public function __construct()
    {
        $this->setPageTemplate(Config::getValue('path.template.login'));
    }

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Recover Account');

        // logout any existing user
        Auth::logout();

        $this->form = new Form();

        $this->form->appendField(new Input('username'))
            ->setAttr('autocomplete', 'off')
            ->setAttr('placeholder', 'Username/Email')
            ->setRequired()
            ->setNotes('Enter your username or email to recover your account.');

        $html = <<<HTML
            <a href="/login">Login</a>
        HTML;
        if (Config::getValue('auth.registration.enable', false)) {
            $html = <<<HTML
                <a href="/register">Register</a> | <a href="/login">Login</a>
            HTML;

        }
        $this->form->appendField(new Html('links', $html))->setLabel('')->addFieldCss('text-center');
        $this->form->appendField(new Submit('recover', [$this, 'onDefault']));

        $load = [];
        $this->form->setFieldValues($load);
        $this->form->execute($_POST);
    }

    public function onDefault(Form $form, Submit $action): void
    {
        if (!$form->getFieldValue('username')) {
            $form->setFieldValue('username', '');
            $form->addError('Please enter a valid username/email.');
            return;
        }

        $auth = Auth::findByUsername(strtolower($form->getFieldValue('username')));
        if (!$auth) {
            $auth = Auth::findByEmail(strtolower($form->getFieldValue('username')));
        }
        if (!($auth && $auth->active)) {
            Alert::addError("Invalid user account");
            Uri::create('/')->redirect();
        }

        /** @var User $user */
        $user = $auth->getDbModel();
        if (!$user) {
            $form->setFieldValue('username', '');
            $form->addFieldError('username', 'Please enter a valid username.');
            return;
        }

        if (\App\Email\User::sendRecovery($user)) {
            Alert::addSuccess('Please check your email for instructions to recover your account.');
        } else {
            Alert::addWarning('Recovery email failed to send. Please <a href="/contact">contact us.</a>');
        }

        Uri::create('/')->redirect();
    }

    public function doRecover(): void
    {
        $this->getPage()->setTitle('Setup Account Password');

        // logout any existing user
        Auth::logout();

        $this->token = GuestToken::getSessionToken();
        if (is_null($this->token)) {
            throw new Exception("You do not have permission to access this page.");
        }

        $this->auth = Auth::findByHash($this->token->payload['h'] ?? '');
        if (is_null($this->auth)) {
            throw new Exception("Invalid user token");
        }

        $this->form = new Form();

        $this->form->appendField(new Password('newPassword'))->setLabel('Password')
            ->setAttr('placeholder', 'Password')
            ->setAttr('autocomplete', 'off')->setRequired();
        $this->form->appendField(new Password('confPassword'))->setLabel('Confirm')
            ->setAttr('placeholder', 'Password Confirm')
            ->setAttr('autocomplete', 'off')->setRequired();

        $this->form->appendField(new Submit('save', [$this, 'onRecover']));

        $load = [];
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);
    }

    public function onRecover(Form $form, Submit $action): void
    {
        if (!$form->getFieldValue('newPassword')  || $form->getFieldValue('newPassword') != $form->getFieldValue('confPassword')) {
            $form->addFieldError('newPassword', 'Invalid Password');
            $form->addFieldError('confPassword', 'Passwords do not match');
        } else {
            $errors = Auth::validatePassword($form->getFieldValue('newPassword'));
            if (count($errors)) {
                $form->addFieldError('newPassword', 'Invalid Password');
                $form->addFieldError('confPassword', implode('<br/>', $errors));
            }
        }

        if ($form->hasErrors()) {
            return;
        }

        $this->auth->password = Auth::hashPassword($form->getFieldValue('newPassword'));
        $this->auth->active = true;
        $this->auth->save();

        $this->token->delete();

        Alert::addSuccess('Account password updated. Please login.');
        Uri::create('/login')->redirect();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        if ($this->form) {
            $template->appendTemplate('content', $this->form->show());
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
    <h1 class="h3 mb-3 fw-normal text-center" var="title">Setup Account Password</h1>
    <div class="" var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}