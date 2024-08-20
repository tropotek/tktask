<?php
namespace App\Controller\Examples;

use Bs\ControllerAdmin;
use Dom\Template;

class Test extends ControllerAdmin
{

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Test');

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $template->appendText('title', 'Test Page');
        $template->setAttr('back', 'href', $this->getBackUrl());


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
    <div class="card-header" var="title"><i class="fa fa-cogs"></i> </div>
    <div class="card-body" var="content">
        <h3 var="title"></h3>



    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }
}


