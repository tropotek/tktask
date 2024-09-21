<?php
namespace App\Controller\User;

use Au\Auth;
use Bs\ControllerDomInterface;
use App\Db\User;
use Bs\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Encrypt;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Html;
use Tk\Form\Field\Input;
use Tk\Form\Field\Password;
use Tk\Uri;

class Register extends ControllerDomInterface
{

    protected ?Form $form = null;

    public function __construct()
    {
        $this->setPageTemplate($this->getConfig()->get('path.template.login'));
    }

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Register');

        if (!$this->getConfig()->get('user.registration.enable', false)) {
            Alert::addError('User registrations are closed for this account');
            Uri::create('/')->redirect();
        }

        $this->form = new Form();

        $this->form->appendField(new Input('name'))
            ->setRequired()
            ->setAttr('placeholder', 'Name');

        $this->form->appendField(new Input('email'))
            ->setRequired()
            ->setAttr('placeholder', 'Email');

        $this->form->appendField(new Input('username'))
            ->setAttr('placeholder', 'Username')
            ->setAttr('autocomplete', 'off')
            ->setRequired();

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
        if (!$this->getConfig()->get('user.registration.enable', false)) {
            Alert::addError('New user registrations are closed for this account');
            Uri::create('/')->redirect();
        }

        $user = new User();
        $auth = $user->getAuth();
        $auth->active = false;
        $user->type   = User::TYPE_MEMBER;

        // set object values from fields
        $form->mapModel($user);
        $form->mapModel($auth);

        if (!$form->getFieldValue('password')  || $form->getFieldValue('password') != $form->getFieldValue('confPassword')) {
            $form->addFieldError('password');
            $form->addFieldError('confPassword');
            $form->addFieldError('confPassword', 'Passwords do not match');
        } else {
            $errors = Auth::validatePassword($form->getFieldValue('password'));
            if (count($errors)) {
                $form->addFieldError('confPassword', implode('<br/>', $errors));
            }
        }

        $form->addFieldErrors($user->validate());

        if ($form->hasErrors()) {
            return;
        }

        $auth->password = Auth::hashPassword($user->password);
        $user->save();
        $auth->save();

        \App\Email\User::sendRegister($user);

        Alert::addSuccess('Please check your email for instructions to activate your account.');
        Uri::create('/')->redirect();
    }

    public function doActivate(): void
    {
        $this->getPage()->setTitle('Register');

        if (!$this->getConfig()->get('user.registration.enable', false)) {
            Alert::addError('New user registrations are closed for this account');
            Uri::create('/')->redirect();
        }

        $token = $_REQUEST['t'] ?? '';
        $arr = Encrypt::create($this->getConfig()->get('system.encrypt'))->decrypt($token);
        $arr = unserialize($arr);
        if (!is_array($arr)) {
            Alert::addError('Unknown account registration error, please try again.');
            Uri::create('/')->redirect();
        }

        $auth = Auth::findByHash($arr['h'] ?? '');
        $user = $auth->getDbModel();

        if (!$user) {
            Alert::addError('Invalid user registration');
            Uri::create('/')->redirect();
        }

        $auth->active = true;
        $user->save();

        Alert::addSuccess('You account has been successfully activated, please login.');
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
    <h1 class="h3 mb-3 fw-normal text-center">Account Registration</h1>
    <div class="" var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}