<?php
namespace App\Controller;

use App\Db\User;
use App\Db\UserMap;
use App\Util\Masquerade;
use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Auth\Result;
use Tk\Date;
use Tk\Form;
use Tk\Uri;

class LoginOrg extends PageController
{
    protected Form $form;

    public function __construct()
    {
        parent::__construct($this->getFactory()->getLoginPage());
        $this->getPage()->setTitle('Login');

        $this->form = Form::create('login');
    }

    public function doLogin(Request $request)
    {
        // Set a token in the session on show, to ensure this browser is the one that requested the login.
        $this->getSession()->set('login', time());

        // check if user already logged in...
        $user = $this->retrieveMe();
        if ($user) {    // remembered user already logged in
            $this->getFactory()->getAuthController()->getStorage()->write($user->getUsername());
            Alert::addSuccess('Logged in successfully');
            Uri::create('/dashboard')->redirect();
        }

        $this->form->appendField(new Form\Field\Input('username'))->setRequired();
        $this->form->appendField(new Form\Field\Input('password'))->setRequired();
        $this->form->appendField(new Form\Field\Checkbox('remember', ['Remember me' => 'remember']));
        $this->form->appendField(new Form\Action\Submit('login', [$this, 'onSubmit']));

        $load = [];
        $this->form->setFieldValues($load);

        $this->form->execute($request->request->all());

        return $this->getPage();
    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action)
    {
        $values = $form->getFieldValues();

        if (Masquerade::isMasquerading()) {
            Masquerade::clearAll();
        }

        $token = $this->getSession()->get('login', 0);
        $this->getSession()->remove('login');
        if ($token + 60*2 < time()) { // login before form token times out
            Alert::addError('Invalid form submission. Please try again.');
            Uri::create()->redirect();
        }

        $result = $this->getFactory()->getAuthController()->clearIdentity()->authenticate($this->getFactory()->getAuthAdapter());
        if ($result->getCode() != Result::SUCCESS) {
            Alert::addError($result->getMessage());
            Uri::create()->redirect();
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

    protected function rememberMe(int $userId, int $day = 30)
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

    public function doLogout(Request $request)
    {
        if (Masquerade::isMasquerading()) {
            Masquerade::masqueradeLogout();
        }
        if ($this->getFactory()->getAuthUser()) {
            $this->getFactory()->getAuthController()->clearIdentity();
            UserMap::create()->deleteToken($this->getFactory()->getAuthUser()->getId());
            setcookie(UserMap::REMEMBER_CID, '', -1);
        }
        Alert::addSuccess('Logged out successfully');
        Uri::create('/')->redirect();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
  <form method="post" id="login-form">
    <h1 class="h3 mb-3 fw-normal">Please sign in</h1>

    <div class="form-floating">
      <input type="text" class="form-control" id="floatingInput" placeholder="name@example.com" name="username" />
      <label for="floatingInput">Username</label>
    </div>
    <div class="form-floating">
      <input type="password" class="form-control" id="floatingPassword" placeholder="Password" name="password" />
      <label for="floatingPassword">Password</label>
    </div>

    <div class="checkbox mb-3">
      <label>
        <input type="checkbox" name="remember" value="remember" /> Remember me
      </label>
    </div>

    <button class="w-100 btn btn-lg btn-primary" type="submit" name="login-login" value="login">Sign in</button>
    <p class="mt-5 mb-3 text-muted">&copy; 2022</p>
  </form>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


