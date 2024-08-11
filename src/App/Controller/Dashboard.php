<?php
namespace App\Controller;

use Bs\ControllerDomInterface;
use Bs\Db\User;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Uri;

class Dashboard extends ControllerDomInterface
{


    public function doDefault(Request $request): void
    {
        $this->getPage()->setTitle('Dashboard');

        if (!$this->getFactory()->getAuthUser()) {
            Alert::addWarning('You do not have permission to access the page: <b>' . Uri::create()->getRelativePath() . '</b>');
            Uri::create('/home')->redirect();
            // $this->getBackUrl()->redirect();
        }

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        if ($this->getFactory()->getAuthUser()) {
            $template->appendHtml('content', "<p><b>My Username:</b> {$this->getFactory()->getAuthUser()->username}</p>");
        }

        $template->setAttr('img', 'src', $this->getAuthUser()->getImageUrl());
        $template->setText('user-name', $this->getAuthUser()->getName());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
<!--  <div class="card shadow mb-3">-->
<!--    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>-->
<!--    <div class="card-body" var="actions">-->
<!--      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>-->
<!--    </div>-->
<!--  </div>-->
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-cogs"></i> <span var="title"></span></div>
    <div class="card-body" var="content">
        <p><img src="#" var="img" /></p>
        <p><b>Name:</b> <span var="user-name"></span></p>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


