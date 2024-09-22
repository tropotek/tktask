<?php
namespace App\Controller\User;

use Au\Auth;
use Bs\ControllerDomInterface;
use App\Db\User;
use Bs\Db\GuestToken;
use Bs\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Encrypt;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Hidden;
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
        $this->setPageTemplate($this->getConfig()->get('path.template.login'));
    }

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Recover');

        // logout any existing user
        Auth::logout();

        $this->form = new Form();

        $this->form->appendField(new Input('username'))
            ->setAttr('autocomplete', 'off')
            ->setAttr('placeholder', 'Username')
            ->setRequired()
            ->setNotes('Enter your username to recover access your account.');

        $html = <<<HTML
            <a href="/login">Login</a>
        HTML;
        if ($this->getConfig()->get('auth.registration.enable', false)) {
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
            $form->addError('Please enter a valid username.');
            return;
        }

        $auth = Auth::findByUsername(strtolower($form->getFieldValue('username')));
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
        $this->getPage()->setTitle('Recover');

        // logout any existing user
        Auth::logout();

        $this->token = GuestToken::getSessionToken();
        if (is_null($this->token)) {
            Alert::addError('You do not have permission to access this page.');
            Uri::create('/')->redirect();
        }

        $this->auth = Auth::findByHash($this->token->payload['h'] ?? '');
        if (is_null($this->auth) || !$this->auth->active) {
            Alert::addError('Invalid user token');
            Uri::create('/')->redirect();
        }

        $this->form = new Form();

        $this->form->appendField(new Password('newPassword'))->setLabel('Password')
            ->setAttr('placeholder', 'Password')
            ->setAttr('autocomplete', 'off')->setRequired();
        $this->form->appendField(new Password('confPassword'))->setLabel('Confirm')
            ->setAttr('placeholder', 'Password Confirm')
            ->setAttr('autocomplete', 'off')->setRequired();

        $this->form->appendField(new Submit('recover', [$this, 'onRecover']));

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
        $this->auth->save();

        $this->token->delete();

        Alert::addSuccess('Successfully account recovery. Please login.');
        Uri::create('/login')->redirect();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        if ($this->form) {
            $template->appendTemplate('content', $this->form->show());
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
    <h1 class="h3 mb-3 fw-normal text-center">Recover Account Password</h1>
    <div class="" var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}