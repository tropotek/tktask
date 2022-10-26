<?php

namespace App\Ui;

use Dom\Renderer\Renderer;
use Dom\Template;
use Tk\Traits\SystemTrait;

class AlertRenderer extends Renderer
{
    use SystemTrait;

    function show(): ?Template
    {
        $template = $this->getTemplate();

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

        return $template;
    }


    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
    <div class="alert alert-dismissible fade show" role="alert" var="panel" repeat="panel">
      <strong var="title"></strong>
      <span var="message"></span>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }


}