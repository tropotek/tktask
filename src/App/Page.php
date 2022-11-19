<?php
namespace App;

use Dom\Template;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Page extends \Bs\Page
{

    public function show(): ?Template
    {
        //$template = $this->getTemplate();

        return parent::show();
    }

}