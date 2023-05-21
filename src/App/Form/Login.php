<?php
namespace App\Form;

use App\Db\User;
use App\Db\UserMap;
use App\Util\Masquerade;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Auth\Result;
use Tk\Date;
use Tk\Form;
use Tk\FormRenderer;
use Tk\Form\Field\Input;
use Tk\Form\Field\Checkbox;
use Tk\Log;
use Tk\Traits\SystemTrait;
use Tk\Uri;

class Login
{
    use SystemTrait;
    use Form\FormTrait;

    public function __construct()
    {
        $this->setForm(Form::create('login'));
    }

    public function doDefault(Request $request)
    {
        // Set a token in the session on show, to ensure this browser is the one that requested the login.
        $this->getSession()->set('login', time());

        // check if user already logged in...
        $user = $this->retrieveMe();
        if ($user) {    // remembered user already logged in
            $this->getFactory()->getAuthController()->getStorage()->write($user->getUsername());
            $this->getFactory()->getSession()->getFlashBag()->add('success', 'Logged in successfully');
            Uri::create('/dashboard')->redirect();
        }

        $this->getForm()->appendField(new Input('username'))->setRequired();
        $this->getForm()->appendField(new Input('password'))->setType('password')->setRequired();
        $this->getForm()->appendField(new Checkbox('remember', ['Remember me' => 'remember']))->setLabel('');

        $html = <<<HTML
            <a href="/recover">Recover</a> | <a href="/register">Register</a>
        HTML;
        $this->getForm()->appendField(new Form\Field\Html('links', $html))->setLabel('');
        $this->getForm()->appendField(new Form\Action\Submit('login', [$this, 'onSubmit']))->addCss('w-100');

        $load = [];
        $this->getForm()->setFieldValues($load);

        $this->getForm()->execute($request->request->all());
    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action)
    {
        $values = $form->getFieldValues();

        if (Masquerade::isMasquerading()) {
            Masquerade::clearAll();
        }

        $token = $this->getSession()->get('login', 0);
        $this->getSession()->remove('login');
        if (($token + 60*2) < time()) { // login before form token times out
            $form->addFieldError('remember', 'Invalid form submission, please try again.');
            return;
        }

        $result = $this->getFactory()->getAuthController()->clearIdentity()->authenticate($this->getFactory()->getAuthAdapter());
        if ($result->getCode() != Result::SUCCESS) {
            Log::error($result->getMessage());
            $form->addFieldError('remember', 'Invalid login details.');
            return;
        }

        // Login successful
        $user = $this->getFactory()->getAuthUser();
        $user->setLastLogin(Date::create('now', $user->getTimezone() ?: null));
        $user->save();

        if (!empty($values['remember'] ?? '')) {
            $this->rememberMe($user->getId());
        } else {
            UserMap::create()->deleteToken($user->getId());
            setcookie(UserMap::REMEMBER_CID, '', -1);
        }

        Uri::create('/dashboard')->redirect();
    }

    protected function rememberMe(int $userId, int $day = 30): void
    {
        [$selector, $validator, $token] = UserMap::create()->generateToken();

        // remove all existing token associated with the user id
        UserMap::create()->deleteToken($userId);

        // set expiration date
        $expired_seconds = time() + 60 * 60 * 24 * $day;

        // insert a token to the database
        $hash_validator = password_hash($validator, PASSWORD_DEFAULT);
        $expiry = date('Y-m-d H:i:s', $expired_seconds);

        if (UserMap::create()->insertToken($userId, $selector, $hash_validator, $expiry)) {
            // TODO: we need to manage the response object so we can call on it when needed.
            //$cookie = Cookie::create('remember', $token, Date::create()->add(new \DateInterval('PT'.$expired_seconds.'S')));
            // use standard php cookie for now.
            setcookie(UserMap::REMEMBER_CID, $token, $expired_seconds);
        }
    }

    protected function retrieveMe(): ?User
    {
        $token = $this->getRequest()->cookies->get(UserMap::REMEMBER_CID, '');
        if ($token) {
            [$selector, $validator] = UserMap::create()->parseToken($token);
            $tokens = UserMap::create()->findTokenBySelector($selector);
            if (password_verify($validator, $tokens['hashed_validator'])) {
                return UserMap::create()->findBySelector($selector);
            }
        }
        return null;
    }

    public function show(): ?Template
    {
        $renderer = new FormRenderer($this->getForm());

        return $renderer->show();
    }

}