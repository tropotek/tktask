<?php
namespace App\Controller\User;

use App\Db\User;
use Bs\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Uri;

class Manager extends PageController
{
    protected \App\Table\User $table;

    protected string $type = User::TYPE_USER;

    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('User Manager');
        $this->getCrumbs()->reset();
    }

    public function doDefault(Request $request, string $type)
    {
        $this->type = $type;
        if ($this->type == User::TYPE_USER) {
            $this->setAccess(User::PERM_MANAGE_USER);
        } else if ($this->type == User::TYPE_STAFF) {
            $this->setAccess(User::PERM_MANAGE_STAFF);
        } else {
            $this->setAccess(User::PERM_ADMIN);
        }

        $this->getPage()->setTitle(ucfirst($this->type) . ' Manager');

        // Get the form template
        $this->table = new \App\Table\User();
        $this->table->doDefault($request, $this->type);

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('create', 'href', Uri::create('/user/'.$this->type.'Edit'));

        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
      <a href="/" title="Create User" class="btn btn-outline-secondary" var="create"><i class="fa fa-user"></i> Create User</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-users"></i> </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}