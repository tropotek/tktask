<?php
namespace App\Controller;

use App\Db\UserMap;
use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Encrypt;
use Tk\Uri;

class Register extends PageController
{
    protected ?\App\Form\Register $form;

    public function __construct()
    {
        parent::__construct($this->getFactory()->getLoginPage());
        $this->getPage()->setTitle('Register');
    }

    public function doDefault(Request $request)
    {
        $this->form = new \App\Form\Register();

        $this->form->doDefault($request);

        return $this->getPage();
    }

    public function doActivate(Request $request)
    {
        //$token = $request->get('t');        // Bug in here that replaces + with a space on POSTS
        $token = $_REQUEST['t'] ?? '';
        $arr = Encrypt::create($this->getConfig()->get('system.encrypt'))->decrypt($token);
        $arr = unserialize($arr);
        if (!is_array($arr)) {
            $this->getFactory()->getSession()->getFlashBag()->add('danger', 'Unknown account registration error, please try again.');
            Uri::create('/home')->redirect();
        }

        $user = UserMap::create()->findByHash($arr['h'] ?? '');
        if (!$user) {
            $this->getFactory()->getSession()->getFlashBag()->add('danger', 'Invalid user registration');
            Uri::create('/home')->redirect();
        }

        $user->setActive(true);
        $user->setNotes('');
        $user->save();

        $this->getFactory()->getSession()->getFlashBag()->add('success', 'You account has been successfully activated, please login.');
        Uri::create('/login')->redirect();

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        if ($this->form) {
            $template->appendTemplate('content', $this->form->show());
        }

        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
    <h1 class="h3 mb-3 fw-normal">Account Registration</h1>
    <div class="" var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


