<?php
namespace App\Controller\TaskLog;

use App\Db\Task;
use App\Db\TaskLog;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Alert;
use Tk\Date;
use Tk\Uri;


/**
 * @deprecated No longer used anywhere, using component instead
 */
class Edit extends ControllerAdmin
{
    protected ?Task              $task    = null;
    protected ?TaskLog           $taskLog = null;
    protected ?\App\Form\TaskLog $form    = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Add Task Log', 'fas fa-tasks');
        $this->validateAccess(User::getAuthUser()?->isStaff() ?? false);

        $taskId = intval($_REQUEST['taskId'] ?? 0);
        $taskLogId = intval($_REQUEST['taskLogId'] ?? 0);

        $this->taskLog = new TaskLog();
        $this->taskLog->taskId = $taskId;
        if ($taskLogId) {
            $this->taskLog = TaskLog::find($taskLogId);
            if (is_null($this->taskLog)) {
                Alert::addError("Cannot find task log");
                User::getAuthUser()->getHomeUrl()->redirect();
            }
        }

        $this->task = Task::find($this->taskLog->taskId);
        if (!$this->task) {
            Alert::addError("Task not found.");
            Breadcrumbs::getBackUrl()->redirect();
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
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

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
HTML;
        return $this->loadTemplate($html);
    }

}