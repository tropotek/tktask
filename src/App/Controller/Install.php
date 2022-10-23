<?php
namespace App\Controller;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Install extends PageController
{


    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('Install');
    }

    public function doDefault(Request $request)
    {

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();



        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
    <h3>Installing Site</h3>
    <p>TODO: Write a script to install the site. After the `composer install` command has been called.</p>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


