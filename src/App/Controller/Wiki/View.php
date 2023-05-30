<?php
namespace App\Controller\Wiki;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

class View extends PageController
{

    public function __construct()
    {
        parent::__construct($this->getFactory()->createPage($this->getSystem()->makePath('/html/wiki.html')));
        $this->getPage()->setTitle('View Wiki Page');
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
        return $this->loadTemplate($html);
    }

}


