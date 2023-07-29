<?php
namespace App\Controller\Examples;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class DomTest extends PageController
{

    public function __construct()
    {
        parent::__construct($this->getFactory()->getAdminPage());
        $this->getPage()->setTitle('Dom Test');
    }

    public function doDefault(Request $request)
    {

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $template->appendText('title', 'This is a dynamic header');
        $template->setVisible('link2');

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
    <h3 var="title"></h3>
    <p var="content">
      This is a DomTemplate test controller
    </p>

    <ul>
      <li><a href="#" var="link1">Link 1</a></li>
      <li choice="link2"><a href="#" var="link2">Link 2</a></li>
      <li><a href="#" var="link3">Link 3</a></li>
      <li><a href="#" var="link4">Link 4</a></li>
      <li><a href="#" var="link5">Link 5</a></li>
    </ul>

    <ul repeat="link">
      <li var="item"><a href="#" var="link"></a></li>
    </ul>

</div>
HTML;
        return $this->loadTemplate($html);
    }
}


