<?php
namespace App\Controller\Example;

use App\Db\User;
use Bs\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

class Edit extends PageController
{
    protected \App\Form\Example $form;


    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('Edit Example');
        $this->setAccess(User::PERM_ADMIN);
    }

    public function doDefault(Request $request, $id)
    {
        // Get the form template
        $this->form = new \App\Form\Example();
        $this->form->doDefault($request, $id);

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        //$template->appendTemplate('content', $this->form->getRenderer()->getTemplate());
        $template->appendTemplate('content', $this->form->show());

        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
  <h2 var="title"></h2>
  <div var="content" class="tk-form-content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }


}