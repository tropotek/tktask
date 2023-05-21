<?php
namespace App\Controller\User;

use App\Db\User;
use Bs\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

class Manager extends PageController
{
    protected \App\Table\User $table;

    protected string $type = User::TYPE_USER;

    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('User Manager');
    }

    public function doDefault(Request $request)
    {
        if (str_ends_with($request->getRequestUri(), 'staffManager')) {
            $this->type = User::TYPE_STAFF;
            $this->setAccess(User::PERM_MANAGE_STAFF);
        } else {
            $this->setAccess(User::PERM_MANAGE_USER);
        }
        $this->getPage()->setTitle(ucfirst($this->type ) . ' Manager');

        // Get the form template
        $this->table = new \App\Table\User($this->type);
        $this->table->doDefault($request);

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        $template->appendTemplate('content', $this->table->show());

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