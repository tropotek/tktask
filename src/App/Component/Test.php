<?php
namespace App\Component;

use Au\Auth;
use Dom\Template;
use Tk\System;
use Tk\Uri;

class Test extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{

    public function doDefault(): string
    {
        if (!Auth::getAuthUser()) return '';
        return $this->show()->toString();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', 'Testing Test');
        $css = <<<CSS
.test-com .card-header {
    background-color: #1abc9c;
}
CSS;
        $template->appendCss($css);

        $js = <<<JS
jQuery(function($) {
    $('.test-com .card-body').append('<p>Javascript Text</p>');
});
JS;
        $template->appendJs($js);

        if (!System::isHtmx()) {
            $template->appendHtml('content', '<p>From controller</p>');
        } else {
            $template->appendHtml('content', '<p>From HTMX</p>');
        }

        $url = Uri::create('/component/test');
        $template->setAttr('btn1', 'hx-get', $url);

        return $template;
    }


    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="test-com">
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-cogs"></i> <span var="title"></span></div>
    <div class="card-body" var="content">
        <p>
        <button type="button" class="btn btn-outline-primary" var="btn1"
            hx-get="#"
            hx-target=".test-com"
            hx-swap="outerHTML">Reload</button>
        </p>
        <p>Test Component ...</p>
    </div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}
