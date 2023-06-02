<?php
namespace App\Controller;

use Bs\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Uri;

class Dashboard extends PageController
{


    public function __construct()
    {
        parent::__construct($this->getFactory()->getUserPage());
        $this->getPage()->setTitle('Dashboard');
        $this->getPage()->getCrumbs()->reset();
    }

    public function doDefault(Request $request)
    {
        if (!$this->getFactory()->getAuthUser()) {
            Alert::addWarning('You do not have permission to access the page: <b>' . Uri::create()->getRelativePath() . '</b>');
            // TODO: get the user homepage from somewhere ???
            Uri::create('/')->redirect();
        }

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        if ($this->getFactory()->getAuthUser()) {

            $template->appendHtml('content', "<p>My Username: <b>{$this->getFactory()->getAuthUser()->getUsername()}</b></p>");
        }

        return $template;
    }

    public function __makeTemplate()
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
    <div class="card-header" var="title"><i class="fa fa-cogs"></i> </div>
    <div class="card-body" var="content">
        <p><a class="nav-link" href="/login">Login</a></p>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


