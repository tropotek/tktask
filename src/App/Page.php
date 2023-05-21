<?php
namespace App;

use Dom\Template;

class Page extends \Bs\Page
{

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        parent::show();

        return $template;
    }

}