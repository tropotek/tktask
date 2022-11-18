<?php
namespace App\Controller;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Exception;
use Tk\Uri;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Home extends PageController
{

    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('Home');
    }

    public function doDefault(Request $request)
    {
        if ($request->query->has('e')) {
            throw new Exception('This is a test exception...', 500);
        }
        $reg = $this->getFactory()->getRegistry();
        $reg->save();

        vd('test');
        vd($reg->all());
        
        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        $template->setAttr('eurl', 'href', Uri::create()->set('e', true));

        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
    <h3 var="title">Welcome Home</h3>
    <p var="content"></p>

    <p var="username"></p>
    <ul>
      <li><a href="#?e" var="eurl">Test Exception</a></li>
      <li><a href="/info" title="Confirmation Dialog Test" data-confirm="<p><em>Are you sure?</em></p>" data-cancel="Nuh!!">Confirm Test</a></li>
      <li><a href="/install">Install Page</a></li>
    </ul>

    <p>&nbsp;</p>
    <p>&nbsp;</p>

</div>
HTML;
        return $this->loadTemplate($html);
    }

}


