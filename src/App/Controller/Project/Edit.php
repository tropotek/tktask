<?php
namespace App\Controller\Project;

use App\Component\StatusLogTable;
use App\Db\Project;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Dom\Template;
use Tk\Alert;
use Tk\Date;
use Tk\Exception;
use Tk\Uri;

class Edit extends ControllerAdmin
{
    protected ?Project $project = null;
    protected ?\App\Form\Project $form = null;


    public function doDefault(): void
    {
        $projectId = intval($_GET['projectId'] ?? 0);

        $this->getPage()->setTitle('Edit Project', 'fas fa-project-diagram');
        $this->validateAccess(User::getAuthUser()?->isStaff() ?? false);

        $this->project = new Project();
        $this->project->userId = User::getAuthUser()->userId;
        if ($projectId) {
            $this->project = Project::find($projectId);
            if (is_null($this->project)) {
                throw new Exception("cannot find project");
            }
        }

        $this->form = new \App\Form\Project($this->project);
        $this->form->execute($_POST);

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        if ($this->project->projectId) {
            $template->setVisible('edit');
            $template->setText('modified', $this->project->modified->format(Date::FORMAT_LONG_DATETIME));
            $template->setText('created', $this->project->created->format(Date::FORMAT_LONG_DATETIME));
            $template->setVisible('components');
        }

        $template->appendTemplate('content', $this->form->show());

        $url = Uri::create('/component/statusLogTable', ['fid' => $this->project->getId(), 'fkey' => $this->project::class]);
        $template->setAttr('statusTable', 'hx-get', $url);

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="row">
  <div class="col">
    <div class="card mb-3">
      <div class="card-header">
        <div class="info-dropdown dropdown float-end" title="Details" choice="edit">
          <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
          <div class="dropdown-menu dropdown-menu-end">
            <p class="dropdown-item"><span class="d-inline-block">Modified:</span> <span var="modified">...</span></p>
            <p class="dropdown-item"><span class="d-inline-block">Created:</span> <span var="created">...</span></p>
          </div>
        </div>
        <i var="icon"></i> <span var="title"></span>
      </div>
      <div class="card-body" var="content"></div>
    </div>
  </div>
  <div class="col-4" choice="components">
    <div hx-get="/component/statusLogTable" hx-trigger="load" hx-swap="outerHTML" var="statusTable">
      <p class="text-center mt-4"><i class="fa fa-fw fa-spin fa-spinner fa-3x"></i><br>Loading...</p>
    </div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}