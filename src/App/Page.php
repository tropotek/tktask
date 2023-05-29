<?php
namespace App;

use Dom\Template;

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

        return $template;
    }

}