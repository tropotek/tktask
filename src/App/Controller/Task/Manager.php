<?php
namespace App\Controller\Task;

use App\Db\Project;
use App\Db\Task;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Alert;
use Tk\Uri;
use Tk\Db;

class Manager extends ControllerAdmin
{

    protected ?\App\Table\Task $table = null;


    public function doDefault(): mixed
    {
        Breadcrumbs::reset();
        $this->getPage()->setTitle('Task Manager');

        if (!User::getAuthUser()?->isStaff()) {
            Alert::addWarning('You do not have permission to access this page');
            User::getAuthUser()?->getHomeUrl()->redirect() ?? Uri::create('/')->redirect();
        }

        $this->table = new \App\Table\Task();
        $this->table->setOrderBy('-created');
        $this->table->setLimit(25);
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $rows = Task::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());

        if ($_GET['act'] ?? false) {
            return $this->doAction($rows);
        }

        return null;
    }

    public function doAction(array $rows): mixed
    {
        $action = trim($_GET['post'] ?? $_GET['act'] ?? '');
        switch ($action) {
            case 'pdf':
                $ren = new \App\Pdf\PdfTaskList($rows, Project::find(1));
                $ren->output();
                break;
            case 'html':
                $ren = new \App\Pdf\PdfTaskList($rows, Project::find(1));
                return $ren->show();  // to show HTML
                break;
        }

        return null;
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->setAttr('btn-pdf', 'href', Uri::create()->set('act', 'pdf'));

        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
      <a href="/taskEdit" title="Create Task" class="btn btn-outline-secondary"><i class="fa fa-plus"></i> Create Task</a>
          <a href="#" class="btn btn-outline-secondary" title="Task Logs PDF" target="_blank" var="btn-pdf"><i class="fa fa-download"></i> <span>Task Logs PDF</span></a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-tasks"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}