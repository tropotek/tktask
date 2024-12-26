<?php
namespace App\Controller\Project;

use App\Component\StatusLogTable;
use App\Db\Project;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Dom\Template;
use Tk\Alert;
use Tk\Exception;
use Tk\Uri;

class Edit extends ControllerAdmin
{
    protected ?Project $project = null;
    protected ?\App\Form\Project $form = null;
    protected ?StatusLogTable $statusLog = null;


    public function doDefault(): void
    {
        $projectId = intval($_GET['projectId'] ?? 0);

        $this->getPage()->setTitle('Edit Project');

        if (!User::getAuthUser()?->isStaff()) {
            Alert::addWarning('You do not have permission to access the page');
            Uri::create('/')->redirect();
        }

        $this->project = new Project();
        $this->project->userId = User::getAuthUser()->userId;
        if ($projectId) {
            $this->project = Project::find($projectId);
            if (!$this->project) {
                throw new Exception("cannot find project");
            }
        }

        $this->form = new \App\Form\Project($this->project);
        $this->form->execute($_POST);

        $this->statusLog = new StatusLogTable($this->project);
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->appendTemplate('content', $this->form->show());

        $html = $this->statusLog->doDefault();
        $template->appendHtml('components', $html);

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="row">
  <div class="col-12">
    <div class="page-actions card mb-3">
      <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
      <div class="card-body" var="actions">
        <a href="/" title="Back" class="btn btn-outline-secondary me-1" var="back"><i class="fa fa-arrow-left"></i> Back</a>
      </div>
    </div>
  </div>
  <div class="col-8">
    <div class="card mb-3">
      <div class="card-header"><i class="fas fa-project-diagram"></i> <span var="title"></span></div>
      <div class="card-body" var="content"></div>
    </div>
  </div>
  <div class="col-4" var="components">

  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}