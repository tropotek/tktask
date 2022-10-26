<?php

namespace App\Controller\User;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Manager extends PageController
{
    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('User Manager');
    }

    public function doDefault(Request $request)
    {


        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());


        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
  <h2 var="title"></h2>
  <div var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }


}