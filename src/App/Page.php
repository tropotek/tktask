<?php
namespace App;

use App\Db\User;
use App\Ui\Customizer;
use App\Ui\Nav;
use Bs\Auth;
use Bs\Menu\MintonRenderer;
use Bs\Registry;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Alert;
use Tk\Config;
use Tk\Date;
use Tk\System;
use Tk\Uri;

class Page extends \Bs\Mvc\Page
{

    public function show(): ?Template
    {
        $template = parent::show();

        if (Registry::getValue('system.meta.keywords')) {
            $template->appendMetaTag('keywords', Registry::getValue('system.meta.keywords', ''));
        }
        if (Registry::getValue('system.meta.description')) {
            $template->appendMetaTag('description', Registry::getValue('system.meta.description', ''));
        }

        $template->appendJs(Registry::getValue('system.global.js', ''));
        $template->appendCss(Registry::getValue('system.global.css', ''));

        if (str_contains($this->getTemplatePath(), '/minton/')) {
            $this->showNavigation($template);
        }

        $template->setText('year', date('Y'));
        $template->setAttr('home', 'href', Uri::create('/')->toString());

        $user = User::getAuthUser();
        if (is_null($user)) {
            $template->setVisible('no-auth');
            $template->setVisible('loggedOut');
        } else {
            $template->setText('username', $user->username);
            $template->setText('user-name', $user->nameShort);
            $template->setText('user-type', ucfirst($user->type));
            $template->setAttr('user-image', 'src', $user->getImageUrl());
            $template->setAttr('user-home-url', 'href', $user->getHomeUrl());

            $template->setVisible('loggedIn');
            $template->setVisible('auth');
        }

        $this->showCrumbs();
        $this->showAlert();
        $this->showLogoutDialog();
        $this->showAbout();
        //$this->showMaintenanceRibbon();

        return $template;
    }

    /**
     * Show a logout confirmation dialog
     */
    protected function showLogoutDialog(): void
    {
        //if (!(Auth::getAuthUser() && isset($_SESSION['_OAUTH']))) return;
        if (!(Auth::getAuthUser())) return;
        $oAuth = $_SESSION['_OAUTH'] ?? '';

        $html = <<<HTML
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form method="get" action="/logout">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="logoutModalLabel">Logout</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to leave?

          <div class="form-check" choice="ssi">
            <input class="form-check-input" type="checkbox" name="ssi" value="1" id="fid-ssi-logout">
            <label class="form-check-label" for="fid-ssi-logout" var="label">
              Logout from Microsoft
            </label>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Logout</button>
        </div>
      </form>
    </div>
  </div>
</div>

HTML;
        $template = $this->loadTemplate($html);

        if ($oAuth && Config::getValue('auth.'.$oAuth.'.endpointLogout', '')) {
            $template->setText('label', 'Logout from ' . ucwords($oAuth));
            $template->setVisible('ssi');
        }

        $js = <<<JS
jQuery(function($) {
    $('.btn-logout').on('click', function() {
        $('#logoutModal').modal('show');
        return false;
    });
});
JS;
        $template->appendJs($js);

        $this->getTemplate()->prependTemplate('content', $template);
    }

    /**
     * To open the dialog:
     *     <a href="#" data-bs-toggle="modal" data-bs-target="#about-modal">About</a>
     */
    protected function showAbout(): void
    {
        $html = <<<HTML
<div id="about-modal" class="modal fade" tabindex="-1" aria-labelledby="about-modal-title" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fs-5" id="about-modal-title"><span var="site-name"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <dl class="row">
          <dt class="col-sm-3">Version</dt>
          <dd class="col-sm-9" var="version"></dd>

          <dt class="col-sm-3">Released</dt>
          <dd class="col-sm-9" var="released"></dd>

          <dt class="col-sm-3">Licence</dt>
          <dd class="col-sm-9">Registered</dd>

          <dt class="col-sm-3">Author</dt>
          <dd class="col-sm-9"><a href="https://www.tropotek.com.au/" target="_blank" var="author">tropotek.com.au</a></dd>
        </dl>

        <p class="float-end mb-0"><small><a href="https://www.tropotek.com/" target="_blank" var="copyright">Tropotek</a></small></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
HTML;
        $template = $this->loadTemplate($html);

        $template->setText('site-name', Registry::getSiteName());
        $template->setText('year', date('Y'));
        $template->setText('version', System::getVersion());
        $template->setText('released', System::getReleaseDate()->format(Date::FORMAT_LONG_DATETIME));

        $template->setHtml('copyright', 'Copyright &copy; ' . \date('Y') . ' ' . Config::getValue('developer.name', 'Undefined'));
        $template->setAttr('copyright', 'href', Uri::create(Config::getValue('developer.web', 'Undefined')));

        $template->setHtml('author', Config::getValue('developer.name', 'Undefined'));
        $template->setAttr('author', 'href', Uri::create(Config::getValue('developer.web', 'Undefined')));

        $this->getTemplate()->appendBodyTemplate($template);
    }

    protected function showNavigation(Template $template): void
    {
        $renderer = new MintonRenderer(Nav::getNavMenu());
        if (basename($this->getTemplatePath()) == 'sn-admin.html') {
            $template->replaceTemplate('side-nav', $renderer->showSideNav());
        } else {
            $template->replaceTemplate('top-nav', $renderer->showTopNav());
        }

        $prof = new MintonRenderer(Nav::getProfileMenu());
        $template->replaceTemplate('profile-nav', $prof->showProfileNav());

        $template->replaceHtml('right-sidebar', Customizer::getHtml());
    }

    protected function showCrumbs(): void
    {
        if (!Breadcrumbs::instance()->isVisible()) return;

        $html = <<<HTML
<div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb" var="crumbs">
      <li class="breadcrumb-item" repeat="item"><a href="#" var="url"></a></li>
    </ol>
  </nav>
</div>
HTML;
        $template = Template::load($html);

        $i = 0;
        $last = Breadcrumbs::count() - 1;
        foreach (Breadcrumbs::toArray() as $url => $title) {
            $repeat = $template->getRepeat('item');

            $repeat->setAttr('url', 'href', $url);
            $repeat->setHtml('url', $title);

            // last item
            if ($i >= $last) {
                //$repeat->setHtml('item', $title); // disable link on last crumb
                $repeat->addCss('item', 'active');
                $repeat->setAttr('item', 'aria-current', 'page');
            }

            $repeat->appendRepeat();
            $i++;
        }

        if ($this->getTemplate()->hasVar('crumbs')) {
            $this->getTemplate()->insertTemplate('crumbs', $template);
        } else {
            $this->getTemplate()->prependTemplate('container', $template);
        }
    }

    protected function showAlert(): void
    {
        if (!Alert::hasAlerts()) return;

        $html = <<<HTML
<div var="alertPanel">
  <div class="alert alert-dismissible fade show" role="alert" repeat="alert">
    <i choice="icon"></i>
    <strong var="title"></strong>
    <span var="message"></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
</div>
HTML;
        $template = $this->loadTemplate($html);

        //$template->setAttr('alertPanel', 'hx-get', Uri::create('/api/htmx/alert'));

        foreach (Alert::getAlerts() as $type => $flash) {
            foreach ($flash as $a) {
                $r = $template->getRepeat('alert');
                $css = strtolower($type);
                if ($css == 'error') $css = 'danger';
                $r->addCss('alert', 'alert-' . $css);
                //$r->setText('title', ucfirst(strtolower($type)));
                $r->setHtml('message', $a->message);
                if ($a->icon) {
                    $r->addCss('icon', $a->icon);
                    $r->setVisible('icon');
                }
                $r->appendRepeat();
            }
        }

        if ($this->getTemplate()->hasVar('alert')) {
            $this->getTemplate()->insertTemplate('alert', $template);
        } else {
            $this->getTemplate()->prependTemplate('content', $template);
        }
    }

    // TODO: Show a maintenance ribbon on the site???
    protected function showMaintenanceRibbon(): void
    {
//        if (!Config::getValue('system.maintenance.enabled')) return;
//        $controller = \Tk\Event\Event::findControllerObject($event);
//        if ($controller instanceof \Bs\Controller\Iface && !$controller instanceof \Bs\Controller\Maintenance) {
//            $page = $controller->getPage();
//            if (!$page) return;
//            $template = $page->getTemplate();
//
//            $html = <<<HTML
//<div class="tk-ribbon tk-ribbon-danger" style="z-index: 99999"><span>Maintenance</span></div>
//HTML;
//            $template->prependHtml($template->getBodyElement(), $html);
//            $template->addCss($template->getBodyElement() ,'tk-ribbon-box');
//        }
    }


}