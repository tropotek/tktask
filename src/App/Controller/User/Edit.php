<?php
namespace App\Controller\User;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Edit extends PageController
{
    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('User Edit');
    }

    public function doDefault(Request $request, $id)
    {
        vd($id);

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
  <h2>User Edit</h2>
  <div var="content"></div>
</div>
HTML;
        return $this->getFactory()->getTemplateLoader()->load($html);
    }


}