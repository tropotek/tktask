<?php
namespace App;

use App\Ui\RightSidebar;
use App\Ui\Nav;
use Dom\Template;
use Tk\Uri;

class Page extends \Bs\Page
{

    public function show(): ?Template
    {
        $template = parent::show();

        if ($this->getRegistry()->get('system.meta.keywords')) {
            $template->appendMetaTag('keywords', $this->getRegistry()->get('system.meta.keywords', ''));
        }
        if ($this->getRegistry()->get('system.meta.description')) {
            $template->appendMetaTag('description', $this->getRegistry()->get('system.meta.description', ''));
        }

        $template->appendJs($this->getRegistry()->get('system.global.js', ''));
        $template->appendCss($this->getRegistry()->get('system.global.css', ''));

        if (str_contains($this->getTemplatePath(), '/minton/')) {
            $this->showMintonParams($template);
        }

        $this->showCrumbs();
        $this->showAlert();
        //$this->showMaintenanceRibbon();

        return $template;
    }

    protected function showMintonParams(Template $template)
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
        $template->replaceHtml('profile-nav', $nav->getProfileNav());
        $template->replaceHtml('right-sidebar', RightSidebar::getHtml());
    }


    protected function showCrumbs()
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


    protected function showAlert()
    {
        if (!count($this->getFactory()->getSession()->getFlashBag()->peekAll())) return;

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

        $template->setAttr('alertPanel', 'hx-get', Uri::create('/api/htmx/alert'));
        foreach ($this->getFactory()->getSession()->getFlashBag()->all() as $type => $flash) {
            foreach ($flash as $a) {
                $a = unserialize($a);
                $r = $template->getRepeat('alert');
                $css = strtolower($type);
                if ($css == 'error') $css = 'danger';
                $r->addCss('alert', 'alert-' . $css);
                //$r->setText('title', ucfirst(strtolower($type)));
                $r->insertHtml('message', $a->message);
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
    protected function showMaintenanceRibbon()
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