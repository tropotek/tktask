<?php
namespace App\Controller\User;

use App\Db\User;
use App\Db\UserMap;
use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Exception;
use Tk\Form;
use Tk\FormRenderer;
use Tk\Form\Field\Input;
use Tk\Form\Field\Checkbox;
use Tk\Uri;


/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Edit extends PageController
{
    protected \App\Form\User $form;


    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('User Edit');
    }

    public function doDefault(Request $request, $id)
    {
        // Get the form template
        $this->form = new \App\Form\User();
        $this->form->doDefault($request, $id);

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        $template->appendTemplate('content', $this->form->getRenderer()->getTemplate());

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