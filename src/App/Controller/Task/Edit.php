<?php
namespace App\Controller\Task;

use App\Component\TaskLogTable;
use App\Db\Task;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Dom\Template;
use Tk\Alert;
use Tk\Uri;

class Edit extends ControllerAdmin
{
    protected ?Task $task = null;
    protected ?\App\Form\Task $form = null;
    protected ?TaskLogTable $taskLog = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Edit Task');

        if (!User::getAuthUser()?->isStaff()) {
            Alert::addWarning('You do not have permission to access this page');
            User::getAuthUser()?->getHomeUrl()->redirect() ?? Uri::create('/')->redirect();
        }

        $taskId = intval($_GET['taskId'] ?? 0);

        $this->task = new Task();
        if ($taskId) {
            $this->task = Task::find($taskId);
            if (!$this->task) {
                Alert::addError("Cannot find task");
                User::getAuthUser()->getHomeUrl()->redirect();
            }
        }

        if ($_GET['ro'] ?? false) {
            $this->doReopen();
        }

        $this->form = new \App\Form\Task($this->task);
        $this->form->execute($_POST);

        if ($this->task->taskId) {
            $this->taskLog = new TaskLogTable($this->task);
        }

        if (!$this->task->isEditable()) {
            foreach ($this->form->getFields() as $field) {
                $field->setReadonly()->setDisabled();
            }
        }
    }

    public function doReopen(): void
    {
        $this->task->reopen();
        \Tk\Alert::addSuccess('The task has been re-opened.');
        Uri::create()->remove('ro')->redirect();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        if ($this->task->isEditable()) {
            $url = Uri::create('/taskLogEdit')->set('taskId', $this->task->taskId);
            $template->setAttr('add-log', 'href', $url);
            $template->setVisible('add-log');
        } else {
            $url = Uri::create()->set('ro', $this->task->taskId);
            $template->setAttr('re-open', 'href', $url);
            $template->setVisible('re-open');
        }

        $url = Uri::create('/taskLogManager')->set('taskId', $this->task->taskId);
        $template->setAttr('logs', 'href', $url);

        if ($this->task->getCost()->getAmount() != 0) {
            $template->setText('billable', "Billable: {$this->task->getCost()->toString()}");
            $template->setVisible('billable');
        }

        $template->appendTemplate('content', $this->form->show());

        $cssCol = 'col-12';
        if ($this->taskLog) {
            $html = $this->taskLog->doDefault();
            $template->appendHtml('secondary', $html);
            $template->setVisible('secondary');
            $cssCol = 'col-7';
        }
        $template->addCss('primary', $cssCol);

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
          <a href="/taskLogEdit" title="Add a new Task Log" class="btn btn-outline-secondary" choice="add-log" data-toggle="modal"><i class="fa fa-fw fa-plus"></i> Add Log</a>
          <a href="#" title="Re-Open this task" class="btn btn-outline-secondary" choice="re-open" data-confirm="Are you sure you want to re-open this task?"><i class="fa fa-fw fa-tasks"></i> Re-Open</a>
          <a href="#" title="Manage Task Logs" class="btn btn-outline-secondary" var="logs"><i class="fa fa-fw fa-tasks"></i> Task Logs</a>
        </div>
      </div>
  </div>
  <div var="primary">
      <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-tasks"></i> <span var="title"></span>
            <div class="float-end" choice="billable">Billable: $0.00</div>
        </div>
        <div class="card-body" var="content"></div>
      </div>
  </div>
  <div class="col-5" choice="secondary"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}