<?php
namespace App\Controller\User;

use Au\Auth;
use Au\Remember;
use Bs\ControllerAdmin;
use Bs\Factory;
use Bs\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Auth\Result;
use Tk\Date;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Html;
use Tk\Form\Field\Input;
use Tk\Form\Field\Password;
use Tk\Log;
use Tk\Uri;

class Login extends ControllerAdmin
{
    protected ?Form $form = null;

    public function __construct()
    {
        $this->setPageTemplate($this->getConfig()->get('path.template.login'));
    }

    public function doLogin(): void
    {
        $this->getPage()->setTitle('Login');

        // check and use remember me token if set
        $auth = Remember::retrieveMe();
        if ($auth) {
            if ($auth instanceof Auth) $auth->getHomeUrl()->redirect();
            Uri::create('/')->redirect();
        }

        $this->form->appendField(new Input('username'))
            ->setRequired()
            ->setAttr('placeholder', 'Username');

        $this->form->appendField(new Password('password'))
            ->setRequired()
            ->setAttr('placeholder', 'Password');

        $this->form->appendField(new Checkbox('remember', ['Remember me' => 'remember']))
            ->setLabel('');

        $html = <<<HTML
            <a href="/recover">Recover</a>
        HTML;
        if ($this->getConfig()->get('user.registration.enable', false)) {
            $html = <<<HTML
                <a href="/recover">Recover</a> | <a href="/register">Register</a>
            HTML;
        }
        $this->form->appendField(new Html('links', $html))->setLabel('')->addFieldCss('text-center');
        $this->form->appendField(new Submit('login', [$this, 'onSubmit']));

        $load = [];
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);
    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $values = $form->getFieldValues();

        $factory = Factory::instance();
        $result = $factory->getAuthController()->authenticate($factory->getAuthAdapter());
        if ($result->getCode() != Result::SUCCESS) {
            Log::debug($result->getMessage());
            $form->addError('Invalid login details.');
            return;
        }

        // Login success
        $auth = Auth::getAuthUser();
        $auth->lastLogin = Date::create('now', $auth->timezone ?: null);
        $auth->sessionId = session_id();
        $auth->save();

        if (!empty($values['remember'] ?? '')) {
            Remember::rememberMe($auth->authId);
        } else {
            Remember::forgetMe($auth->authId);
        }

        if ($auth instanceof Auth) $auth->getHomeUrl()->redirect();
        Uri::create('/')->redirect();
    }

    public function doLogout(): void
    {
        Auth::logout();
        Alert::addSuccess('Logged out successfully');
        Uri::create('/')->redirect();
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
    <h1 class="text-center h3 mb-3 fw-normal">Login</h1>
    <div var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}