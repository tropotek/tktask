<?php
namespace App\Controller;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Uri;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Dashboard extends PageController
{


    public function __construct()
    {
        parent::__construct($this->getFactory()->getUserPage());
        $this->getPage()->setTitle('Dashboard');
    }

    public function doDefault(Request $request)
    {


        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $template->appendHtml('content', "<p>My Username: <b>{$this->getFactory()->getAuthUser()}</b></p>");

        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
    <h3 var="title">Dashboard</h3>

    <div var="content"></div>

</div>
HTML;
        return $this->loadTemplate($html);
    }

}


