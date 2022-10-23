<?php
namespace App\Controller;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Exception;
use Tk\Uri;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Home extends PageController
{

    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('Home');
    }

    public function doDefault(Request $request)
    {
        if ($request->query->has('e')) {
            throw new Exception('This is a test exception...', 500);
        }

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $url = Uri::create();

        $template->setAttr('eurl', 'href', Uri::create()->set('e', true));

        if ($this->getFactory()->getAuthUser()) {
            $template->appendHtml('user', "<p>My Username: <b>{$this->getFactory()->getAuthUser()}</b></p>");
            $template->setVisible('user');
        }

        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
    <h3 var="title">Welcome Home</h3>
    <p var="content"></p>

    <p var="user"></p>
    <ul>
      <li><a href="#?e" var="eurl">Test Exception</a></li>
      <li><a href="domTest">Template Test</a></li>
      <li><a href="info">phpinfo</a></li>
      <li><a href="install">Install</a></li>
      <hr />
      <li choice="user"><a href="userManager">User Manager</a></li>
      <li choice="user"><a href="userEdit/1234">User Edit</a></li>
      <li><a href="dashboard">My Dashboard</a></li>
    </ul>

    <ul repeat="link">
      <li var="item"><a href="#" var="link"></a></li>
    </ul>

</div>
HTML;
        return $this->getFactory()->getTemplateLoader()->load($html);
    }

}


