<?php
namespace App\Controller;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

class Recover extends PageController
{
    protected ?\App\Form\Recover $form;

    public function __construct()
    {
        parent::__construct($this->getFactory()->getLoginPage());
        $this->getPage()->setTitle('Recover');
    }

    public function doDefault(Request $request)
    {
        $this->form = new \App\Form\Recover();

        $this->form->doDefault($request);

        return $this->getPage();
    }

    public function doRecover(Request $request)
    {
        $this->form = new \App\Form\Recover();

        $this->form->doRecover($request);

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
    <h1 class="h3 mb-3 fw-normal">Recover</h1>
    <div class="" var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


