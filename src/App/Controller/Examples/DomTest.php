<?php
namespace App\Controller\Examples;

use Bs\ControllerAdmin;
use Dom\Template;

class DomTest extends ControllerAdmin
{


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Dom Test');


    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $template->appendText('title', 'This is a dynamic header');
        $template->setAttr('back', 'href', $this->getBackUrl());
        $template->setVisible('link2');

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
        <p var="content">
            This is a DomTemplate test controller
        </p>

        <ul>
            <li><a href="#" var="link1">Link 1</a></li>
            <li choice="link2"><a href="#" var="link2">Link 2</a></li>
            <li><a href="#" var="link3">Link 3</a></li>
            <li><a href="#" var="link4">Link 4</a></li>
            <li><a href="#" var="link5">Link 5</a></li>
        </ul>

        <ul repeat="link">
            <li var="item"><a href="#" var="link"></a></li>
        </ul>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }
}


