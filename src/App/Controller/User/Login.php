<?php
namespace App\Controller\User;

use Bs\Auth;
use Bs\Db\Remember;
use Bs\Mvc\ControllerAdmin;
use Bs\Factory;
use Bs\Mvc\Form;
use Bs\Ui\Breadcrumbs;
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

        $this->form = new Form();

        $this->form->appendField(new Input('username'))
            ->setRequired()
            ->setAttr('placeholder', 'Username');

        $this->form->appendField(new Password('password'))
            ->setRequired()
            ->setAttr('placeholder', 'Password');

        $this->form->appendField(new Checkbox('remember', ['remember' => 'Remember Me']))
            ->setLabel('');

        $html = <<<HTML
            <a href="/recover">Recover</a>
        HTML;
        if ($this->getConfig()->get('auth.registration.enable', false)) {
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
        Breadcrumbs::reset();

        // SSI logout
        $ssiLogout = truefalse($_GET['ssi'] ?? false);
        if (isset($_SESSION['_OAUTH']) && $ssiLogout) {
            $oAuth = trim($_SESSION['_OAUTH']);
            unset($_SESSION['_OAUTH']);
            if ($this->getConfig()->get('auth.'.$oAuth.'.endpointLogout', '')) {
                $url = Uri::create($this->getConfig()->get('auth.'.$oAuth.'.endpointLogout'));
                if ($oAuth == Auth::EXT_MICROSOFT) {
                    $url->set('post_logout_redirect_uri', Uri::create('/')->toString());
                }
                $url->redirect();
            }
        }

        Alert::addSuccess('Logged out successfully');
        Uri::create('/')->redirect();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        if ($this->form) {
            $template->appendTemplate('content', $this->form->show());
        }

        $oauthUrl = Uri::create('/_ssi');

        $hasExternal = false;
        if ($this->getConfig()->get('auth.microsoft.enabled', false)) {
            $hasExternal = true;
            $template->setVisible('microsoft');
            $url = Uri::create('https://login.microsoftonline.com/common/oauth2/v2.0/authorize')
                ->set('state', 'microsoft')
                ->set('approval_prompt', 'auto')
                ->set('response_type', 'code')
                ->set('redirect_uri', $oauthUrl)
                ->set('client_id', $this->getConfig()->get('auth.microsoft.clientId', ''))
                ->set('scope', $this->getConfig()->get('auth.microsoft.scope', ''));
            $template->setAttr('microsoft', 'href', $url);
        }

        if ($this->getConfig()->get('auth.google.enabled', false)) {
            $hasExternal = true;
            $template->setVisible('google');
            $url = Uri::create('https://accounts.google.com/o/oauth2/auth')
                ->set('state', 'google')
                ->set('access_type', 'online')
                ->set('response_type', 'code')
                ->set('redirect_uri', $oauthUrl)
                ->set('scope', $this->getConfig()->get('auth.google.scope', ''))
                ->set('client_id', $this->getConfig()->get('auth.google.clientId', ''));
            $template->setAttr('google', 'href', $url);
        }

        if ($this->getConfig()->get('auth.facebook.enabled', false)) {
            $hasExternal = true;
            $template->setVisible('facebook');
            $url = Uri::create('https://www.facebook.com/dialog/oauth')
                ->set('state', 'facebook')
                ->set('response_type', 'code')
                ->set('scope', $this->getConfig()->get('auth.facebook.scope', ''))
                ->set('redirect_uri', $oauthUrl)
                ->set('client_id', $this->getConfig()->get('auth.facebook.clientId', ''));
            $template->setAttr('facebook', 'href', $url);
        }

        $template->setVisible('ext', $hasExternal);

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <h1 class="text-center h3 mb-3 fw-normal">Login</h1>
  <div var="content"></div>

  <div class="external mt-4" choice="ext">
    <a href="#" class="btn btn-primary col-12 mb-2" choice="microsoft"><i class="fa-brands fa-fw fa-windows"></i> Microsoft</a>
    <a href="#" class="btn btn-warning col-12 mb-2" choice="google"><i class="fa-brands fa-fw fa-google"></i> Google</a>
    <a href="#" class="btn btn-info col-12 mb-2" choice="facebook"><i class="fa-brands fa-fw fa-facebook"></i> Facebook</a>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}