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


    public function doDefault(): void
    {
        $projectId = intval($_GET['projectId'] ?? 0);

        $this->getPage()->setTitle('Edit Project');
        $this->validateAccess(User::getAuthUser()?->isStaff() ?? false);

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

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->appendTemplate('content', $this->form->show());

        $url = Uri::create('/component/statusLogTable', ['fid' => $this->project->getId(), 'fkey' => $this->project::class]);
        $template->setAttr('statusTable', 'hx-get', $url);

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
        <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
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
    <div hx-get="/component/statusLogTable" hx-trigger="load" hx-swap="outerHTML" var="statusTable">
      <p class="text-center mt-4"><i class="fa fa-fw fa-spin fa-spinner fa-3x"></i><br>Loading...</p>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}