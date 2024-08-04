<?php
namespace App\Controller\Admin;

use Bs\ControllerDomInterface;
use Bs\Db\User;
use Bs\Form\EditTrait;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

class Settings extends ControllerDomInterface
{
    use EditTrait;


    public function doDefault(Request $request): void
    {
        $this->getPage()->setTitle('Edit Settings');
        $this->setAccess(User::PERM_SYSADMIN);

        $this->getRegistry()->save();
        $this->getCrumbs()->reset();


        $this->setForm(new \App\Form\Settings());
        $this->getForm()->enableTemplateSelect(str_contains($this->getPage()->getTemplatePath(), '/minton/'));
        $this->getForm()->init()->execute($request->request->all());


    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->appendTemplate('content', $this->getForm()->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-white" var="back"><i class="fa fa-arrow-left"></i> Back</a>
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