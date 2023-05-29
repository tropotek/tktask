<?php
namespace App\Listener;

use Dom\Mvc\Page;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tk\Log;
use Tk\Traits\SystemTrait;
use Tk\Uri;

class AlertHandler implements EventSubscriberInterface
{
    use SystemTrait;


    public function onView(ViewEvent $event)
    {
        $page = $event->getControllerResult();
        if (!$page instanceof Page) return;

        $html = <<<HTML
<div var="alertPanel">
    <div class="alert alert-dismissible fade show" role="alert" repeat="panel">
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
                $r = $template->getRepeat('panel');
                $css = strtolower($type);
                if ($css == 'error') $css = 'danger';
                $r->addCss('panel', 'alert-' . $css);
                $r->setText('title', ucfirst(strtolower($type)));
                $r->insertHtml('message', $a->message);
                if ($a->icon) {
                    $r->addCss('icon', $a->icon);
                    $r->setVisible('icon');
                }
                $r->appendRepeat();
            }
        }
        $page->getTemplate()->prependTemplate('container', $template);

    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => 'onView',
        ];
    }

}