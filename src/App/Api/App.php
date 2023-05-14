<?php
namespace App\Api;

use App\Db\UserMap;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tk\Traits\SystemTrait;
use Tk\Uri;

class App
{
    use SystemTrait;


    public function doAlert(Request $request)
    {
        $html = <<<HTML
<div hx-get="" hx-trigger="submit from:form" hx-target="this" hx-swap="outerHTML" var="alertPanel">
    <div class="alert alert-dismissible fade show" role="alert" repeat="panel">
      <strong var="title"></strong>
      <span var="message"></span>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
HTML;

        $template = $this->loadTemplate($html);

        $template->setAttr('alertPanel', 'hx-get', Uri::create('/api/htmx/alert'));
        foreach ($this->getFactory()->getSession()->getFlashBag()->all() as $type => $flash) {
            foreach ($flash as $msg) {
                $r = $template->getRepeat('panel');
                $css = strtolower($type);
                if ($css == 'error') $css = 'danger';
                $r->addCss('panel', 'alert-' . $css);
                $r->setText('title', ucfirst(strtolower($type)));
                $r->insertHtml('message', $msg);
                $r->appendRepeat();
            }
        }

        return $template->toString();
    }

    public function doToast(Request $request)
    {
        $toasts = <<<HTML
<div aria-live="polite" aria-atomic="true" class="toastPanel position-relative" var="alertPanel"
  hx-get="/api/htmx/toast" hx-trigger="submit from:form" hx-sync="form:queue last" hx-target="this" hx-swap="outerHTML"
>
  <div class="toast-container top-0 end-0 p-3">
    <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" repeat="panel">
      <div class="toast-header">
        <!--<img src="..." class="rounded mr-2" alt="...">-->
        <svg class="bd-placeholder-img rounded mr-2" width="20" height="20" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img"><rect fill="#007aff" width="100%" height="100%" var="svg" /></svg>&nbsp;
        <strong class="me-auto" var="title"> Alert</strong>
        <small class="text-muted" var="time"></small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" var="message"></div>
    </div>
  </div>
</div>
HTML;

        $template = $this->loadTemplate($toasts);

        $template->setAttr('alertPanel', 'hx-get', Uri::create('/api/htmx/toast'));
        foreach ($this->getFactory()->getSession()->getFlashBag()->all() as $type => $flash) {
            foreach ($flash as $msg) {
                $r = $template->getRepeat('panel');
                $colorMap = [
                    'primary'   => '#0d6efd',
                    'secondary' => '#0d6efd',
                    'success'   => '#198754',
                    'info'      => '#0dcaf0',
                    'warning'   => '#ffc107',
                    'danger'    => '#dc3545',
                    'error'     => '#dc3545',
                    'light'     => '#f8f9fa',
                    'dark'      => '#212529',
                ];

                $r->setAttr('svg', 'fill', $colorMap[$type]);
                $r->setText('title', ucfirst(strtolower($type)));
                $r->insertHtml('message', $msg);
                $r->appendRepeat();
            }
        }

        return $template->toString();
    }

}

