<?php
namespace App\Controller\TaskLog;

use App\Db\Task;
use App\Db\TaskLog;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Dom\Template;
use Tk\Alert;
use Tk\Date;
use Tk\Uri;

class Edit extends ControllerAdmin
{
    protected ?Task              $task    = null;
    protected ?TaskLog           $taskLog = null;
    protected ?\App\Form\TaskLog $form    = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Add Task Log');
        $this->validateAccess(User::getAuthUser()?->isStaff() ?? false);

        $taskId = intval($_GET['taskId'] ?? 0);
        $taskLogId = intval($_GET['taskLogId'] ?? 0);

        $this->taskLog = new TaskLog();
        $this->taskLog->taskId = $taskId;
        if ($taskLogId) {
            $this->taskLog = TaskLog::find($taskLogId);
            if (!$this->taskLog) {
                Alert::addError("Cannot find task log");
                User::getAuthUser()->getHomeUrl()->redirect();
            }
        }

        $this->task = Task::find($this->taskLog->taskId);
        if (!$this->task) {
            Alert::addError("Task not found.");
            $this->getBackUrl()->redirect();
        }

        $this->form = new \App\Form\TaskLog($this->taskLog);
        $this->form->setCsrfTtl(0);
        $this->form->execute($_POST);

        // TODO: see if this is suitable here for closed tasks
        if (!$this->task->isEditable()) {
            foreach ($this->form->getFields() as $field) {
                $field->setReadonly()->setDisabled();
            }
        }
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl()->set('taskId', $this->task->taskId));

        if ($this->taskLog->taskLogId) {
            $template->setVisible('edit');
            $template->setText('modified', $this->taskLog->modified->format(Date::FORMAT_LONG_DATETIME));
            $template->setText('created', $this->taskLog->created->format(Date::FORMAT_LONG_DATETIME));
        }

        $template->appendTemplate('content', $this->form->show());

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
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header">
      <div class="info-dropdown dropdown float-end" title="Details" choice="edit">
        <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
        <div class="dropdown-menu dropdown-menu-end">
          <p class="dropdown-item"><span class="d-inline-block">Modified:</span> <span var="modified">...</span></p>
          <p class="dropdown-item"><span class="d-inline-block">Created:</span> <span var="created">...</span></p>
        </div>
      </div>
      <i class="fas fa-tasks"></i> <span var="title"></span>
    </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}