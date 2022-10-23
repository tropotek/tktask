<?php

namespace App\Controller\User;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;


class Manager extends PageController
{
    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->getTemplate()->setTitleText('Home');
    }

    public function doDefault(Request $request)
    {


        return $this->getPage();
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
  <div var="content"></div>
</div>
HTML;
        return $this->getFactory()->getTemplateLoader()->load($html);
    }


}