<?php
namespace App\Controller\Examples;

use App\Db\UserMap;
use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Exception;
use Tk\Uri;


/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class EditHtmx extends PageController
{

    protected \App\Form\User $form;


    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('User Edit');
    }

    public function doDefault(Request $request, $id)
    {
        $user = UserMap::create()->find($id);
        if (!$user) {
            throw new Exception('Invalid User ID: ' . $id);
        }

        // Get the form template

        $this->form = new \App\Form\User();
        // Enable HTMX
        $this->form->getForm()->setAttr('hx-post', Uri::create('/form/user/'.$id));
        $this->form->getForm()->setAttr('hx-target', 'this');
        $this->form->getForm()->setAttr('hx-swap', 'outerHTML');
        $this->form->doDefault($request, $id);

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        $template->replaceTemplate('content', $this->form->getRenderer()->getTemplate());

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