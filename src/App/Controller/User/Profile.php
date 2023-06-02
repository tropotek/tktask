<?php
namespace App\Controller\User;

use App\Db\User;
use Bs\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * TODO: Create a user profile edit page.
 */
class Profile extends PageController
{
    protected \App\Form\User $form;


    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('Edit User');
        $this->setAccess(User::PERM_MANAGE_USER | User::PERM_MANAGE_STAFF);
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

        //$template->appendTemplate('content', $this->form->getRenderer()->getTemplate());
        $template->appendTemplate('content', $this->form->show());

        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-user"></i> </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }


}