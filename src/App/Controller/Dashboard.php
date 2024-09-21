<?php
namespace App\Controller;

use App\Db\User;
use Au\Auth;
use Bs\ControllerAdmin;
use Dom\Template;
use Tk\Alert;
use Tk\Exception;
use Tk\Uri;

class Dashboard extends ControllerAdmin
{

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Dashboard');
        $this->getCrumbs()->reset();

        if (isset($_GET['e'])){
            throw new Exception('This is a test exception...', 500);
        }
        if (isset($_GET['a'])) {
            Alert::addSuccess('This is a success alert', '', 'fa-solid fa-circle-check');
            Alert::addInfo('This is a info alert', '', 'fa-solid fa-circle-info');
            Alert::addWarning('This is a warning alert', '', 'fa-solid fa-triangle-exclamation');
            Alert::addError('This is a error alert', '', 'fa-solid fa-circle-exclamation');
            Uri::create()->remove('a')->redirect();
        }

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

        /** @var User $user */
        $user = Auth::getAuthUser()->getDbModel();
        $template->setAttr('img', 'src', $user->getImageUrl());
        $template->setText('user-name', $user->nameShort);

        $template->setAttr('eurl', 'href', Uri::create()->set('e', true));
        $template->setAttr('aurl', 'href', Uri::create()->set('a', true));

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-cogs"></i> <span var="title"></span></div>
    <div class="card-body" var="content">
        <p><img src="#" var="img" /></p>

        <p>
          <a href="#?e" class="btn btn-outline-dark" var="eurl">Test Exception</a>
          <a href="/info" class="btn btn-outline-dark" title="Confirmation Dialog Test" data-confirm="<p><em>Are you sure?</em></p>" data-cancel="Nuh!!">Confirm Test</a>
          <a href="#?a" class="btn btn-outline-dark" var="aurl">Alert Test</a>
        </p>

        <p><b>Name:</b> <span var="user-name"></span></p>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


