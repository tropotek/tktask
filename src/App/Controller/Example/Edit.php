<?php
namespace App\Controller\Example;

use App\Db\Example;
use App\Db\ExampleMap;
use Bs\Db\User;
use Bs\Form\EditTrait;
use Bs\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Exception;

class Edit extends PageController
{
    use EditTrait;

    protected ?Example $example = null;


    public function __construct()
    {
        parent::__construct();
        $this->getPage()->setTitle('Edit Example 2');
        $this->setAccess(User::PERM_ADMIN);
    }

    public function doDefault(Request $request): \App\Page|\Dom\Mvc\Page
    {
        $this->example = new Example();
        if ($request->query->getInt('exampleId')) {
            $this->example = ExampleMap::create()->find($request->query->getInt('exampleId'));
        }
        if (!$this->example) {
            throw new Exception('Invalid Example ID: ' . $request->query->getInt('exampleId'));
        }

        $this->setForm(new \App\Form\Example($this->example));
        $this->getForm()->init()->execute($request->request->all());

        return $this->getPage();
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