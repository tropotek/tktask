<?php
namespace App\Controller;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Auth\Result;
use Tk\Uri;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Login extends PageController
{


    public function __construct()
    {
        parent::__construct($this->getFactory()->getLoginPage());
        $this->getPage()->setTitle('Login');
    }

    public function doDefault(Request $request)
    {
        if ($request->request->has('login')) {
            $this->onSubmit($request);
        }

        return $this->getPage();
    }

    public function onSubmit(Request $request)
    {
        $result = $this->getFactory()->getAuthController()->clearIdentity()->authenticate($this->getFactory()->getAuthAdapter());

        if ($result->getCode() != Result::SUCCESS) {
            $this->getFactory()->getSession()->getFlashBag()->add('error', $result->getMessage());
            Uri::create()->redirect();
        }

        Uri::create('/dashboard')->redirect();
    }

    public function doLogout(Request $request)
    {
        $this->getFactory()->getAuthController()->clearIdentity();
        $this->getFactory()->getSession()->getFlashBag()->add('success', 'Logged out successfully');
        Uri::create('/')->redirect();

        //return $this->getPage();
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
  <form method="post">
    <h1 class="h3 mb-3 fw-normal">Please sign in</h1>

    <div class="form-floating">
      <input type="text" class="form-control" id="floatingInput" placeholder="name@example.com" name="username" />
      <label for="floatingInput">Username</label>
    </div>
    <div class="form-floating">
      <input type="password" class="form-control" id="floatingPassword" placeholder="Password" name="password"/>
      <label for="floatingPassword">Password</label>
    </div>

    <div class="checkbox mb-3" choice="remember">
      <label>
        <input type="checkbox" value="remember-me" /> Remember me
      </label>
    </div>

    <button class="w-100 btn btn-lg btn-primary" type="submit" name="login">Sign in</button>
    <p class="mt-5 mb-3 text-muted">&copy; 2017–2022</p>
  </form>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


