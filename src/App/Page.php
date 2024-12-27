<?php
namespace App;

use App\Db\User;
use App\Ui\Customizer;
use App\Ui\Nav;
use App\Ui\Notify;
use Bs\Auth;
use Bs\Registry;
use Dom\Template;
use Tk\Alert;
use Tk\Uri;

class Page extends \Bs\Mvc\Page
{

    public function show(): ?Template
    {
        $template = parent::show();

        if (Registry::instance()->get('system.meta.keywords')) {
            $template->appendMetaTag('keywords', Registry::instance()->get('system.meta.keywords', ''));
        }
        if (Registry::instance()->get('system.meta.description')) {
            $template->appendMetaTag('description', Registry::instance()->get('system.meta.description', ''));
        }

        $template->appendJs(Registry::instance()->get('system.global.js', ''));
        $template->appendCss(Registry::instance()->get('system.global.css', ''));

        if (str_contains($this->getTemplatePath(), '/minton/')) {
            $this->showMintonParams($template);
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
        //$this->showMaintenanceRibbon();

        $notify = new Notify();
        $template->replaceTemplate('tk-notify', $notify->show());

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

        if ($oAuth && $this->getConfig()->get('auth.'.$oAuth.'.endpointLogout', '')) {
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

    protected function showMintonParams(Template $template): void
    {
        $this->getFactory()->getCrumbs()->setCssList()->addCss('m-0');

        $nav = new Nav();
        if (basename($this->getTemplatePath()) == 'sn-admin.html') {
            $nav->setAttr('id', 'side-nav');
            $template->replaceHtml('side-nav', $nav->getSideNav());
        } else {
            $nav->setAttr('id', 'top-nav');
            $template->replaceHtml('top-nav', $nav->getTopNav());
        }
        $template->replaceTemplate('profile-nav', $nav->getProfileNav());
        $template->replaceHtml('right-sidebar', Customizer::getHtml());
    }

    protected function showCrumbs(): void
    {
        $crumbs = $this->getFactory()->getCrumbs();
        if (!($crumbs && $crumbs->isVisible())) return;

        if (!$template = $crumbs->show()) {
            return;
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
//        if (!$this->getConfig()->get('system.maintenance.enabled')) return;
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