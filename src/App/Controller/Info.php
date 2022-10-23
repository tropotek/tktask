<?php
namespace App\Controller;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Info extends PageController
{

    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('PHP Info');
    }

    public function doDefault(Request $request)
    {
        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        ob_start();
        phpinfo();
        $ob = ob_get_clean();
        $ob1 = tidy_repair_string($ob, ['output-xhtml' => true, 'show-body-only' => true], 'utf8');
        $template->appendHtml('content', $ob1);

        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div var="content"></div>
HTML;
        return $this->getFactory()->getTemplateLoader()->load($html);
    }

}


