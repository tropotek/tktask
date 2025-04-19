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
        $this->getPage()->setTitle('Task Manager', 'fas fa-tasks');
        $this->validateAccess(User::getAuthUser()?->isStaff() ?? false);

        $this->table = new \App\Table\Task();
        $this->table->setOrderBy('-created');
        $this->table->setLimit(25);
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $rows = Task::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());

        return null;
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        //$url = Uri::create()->set('act', 'pdf');
        $url = Uri::create('/pdf/taskList')->set('', 'pdf');
        $template->setAttr('btn-pdf', 'href', $url);

        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-body" var="actions">
      <a href="/taskEdit" title="Create Task" class="btn btn-outline-secondary"><i class="fa fa-plus"></i> Create Task</a>
          <a href="#" class="btn btn-outline-secondary" title="Task Logs PDF" target="_blank" var="btn-pdf"><i class="fa fa-download"></i> <span>Task Logs PDF</span></a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i var="icon"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}