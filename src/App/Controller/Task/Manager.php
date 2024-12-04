<?php
namespace App\Controller\Task;

use App\Db\Task;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Alert;
use Tk\Uri;
use Tk\Db;

class Manager extends ControllerAdmin
{

    protected ?Table $table = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Task Manager');
        $this->getCrumbs()->reset();

        if (!User::getAuthUser()?->isStaff()) {
            Alert::addWarning('You do not have permission to access this page');
            User::getAuthUser()?->getHomeUrl()->redirect() ?? Uri::create('/')->redirect();
        }

        $this->table = new \App\Table\Task();
        // TODO: need to find a way around commas in brackets????
        //$this->table->setOrderBy('FIELD(`status`, "open", "pending", "hold", "closed", "cancelled"), created DESC');
        $this->table->setOrderBy('-created');
        $this->table->setLimit(25);
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $rows = Task::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

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