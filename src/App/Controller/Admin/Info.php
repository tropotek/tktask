<?php
namespace App\Controller\Admin;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

class Info extends PageController
{

    public function __construct()
    {
        parent::__construct($this->getFactory()->getAdminPage());
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

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-cogs"></i> PHPinfo</div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


