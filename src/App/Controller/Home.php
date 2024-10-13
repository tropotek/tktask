<?php
namespace App\Controller;

use Bs\Auth;
use Bs\Mvc\ControllerPublic;
use Dom\Template;
use Tk\Alert;
use Tk\Exception;
use Tk\Uri;

class Home extends ControllerPublic
{

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Home');

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
        $reg = $this->getFactory()->getRegistry();
        $reg->save();


    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        $template->setAttr('eurl', 'href', Uri::create()->set('e', true));
        $template->setAttr('aurl', 'href', Uri::create()->set('a', true));

        if (Auth::getAuthUser()) {
            $template->setVisible('auth');
        } else {
            $template->setVisible('no-auth');
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-envelope"></i> </div>
    <div class="card-body" var="content">
        <div>
            <h3 var="title">Welcome Home</h3>
            <p var="content"></p>

            <p choice="auth">Status: You are logged in!</p>
            <p  choice="no-auth">Status You are not logged in</p>

            <ul>
              <li><a href="#?e" var="eurl">Test Exception</a></li>
              <li><a href="/info" title="Confirmation Dialog Test" data-confirm="<p><em>Are you sure?</em></p>" data-cancel="Nuh!!">Confirm Test</a></li>
              <li><a href="#?a" var="aurl">Alert Test</a></li>
            </ul>
            <p>&nbsp;</p>
        </div>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


