<?php
namespace App\Controller\Example;

use Bs\ControllerDomInterface;
use Bs\Table\ManagerTrait;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

class Manager extends ControllerDomInterface
{
    use ManagerTrait;


    public function doDefault(Request $request)
    {
        $this->getPage()->setTitle('Example Manager');
        $this->getCrumbs()->reset();

        $this->setTable(new \App\Table\Example());
        //$this->getTable()->resetTableSession();
        $this->getTable()->init();
        $this->getTable()->findList([], $this->getTable()->getTool('name'));
        $this->getTable()->execute($request);

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->appendTemplate('content', $this->getTable()->show());

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
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}