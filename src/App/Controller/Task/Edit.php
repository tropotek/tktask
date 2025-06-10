<?php
namespace App\Controller\Task;

use App\Db\Task;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Dom\Template;
use Tk\Alert;
use Tk\Date;
use Tk\Uri;

class Edit extends ControllerAdmin
{
    protected ?Task $task = null;
    protected ?\App\Form\Task $form = null;


    public function doDefault(): mixed
    {
        $this->getPage()->setTitle('Edit Task', 'fas fa-tasks');
        $this->validateAccess(User::getAuthUser()?->isStaff() ?? false);

        $action = trim($_REQUEST['action'] ?? '');
        $taskId = intval($_REQUEST['taskId'] ?? 0);

        $this->task = new Task();
        if ($taskId) {
            $this->task = Task::find($taskId);
            if (is_null($this->task)) {
                Alert::addError("Cannot find task");
                User::getAuthUser()->getHomeUrl()->redirect();
            }
        }

        match ($action) {
            'open' => $this->doReopen(),
            'close' => $this->doClose(),
            'close-invoice' => $this->doCloseInvoice(),
            'cancel' => $this->doCancel(),
            default => null,
        };

        if ($action == 'open') {
            $this->doReopen();
        }

        $this->form = new \App\Form\Task($this->task);
        $this->form->execute($_POST);

        if (!$this->task->isEditable()) {
            foreach ($this->form->getFields() as $field) {
                $field->setReadonly()->setDisabled();
            }
        }

        return null;
    }

    public function doReopen(): void
    {
        $this->task->reopen();
        \Tk\Alert::addSuccess('The task has been re-opened.');
        Uri::create()->remove('action')->redirect();
    }

    public function doClose(): void
    {
        $this->task->close();
        \Tk\Alert::addSuccess('The task has been closed.');
        Uri::create()->remove('action')->redirect();
    }

    public function doCloseInvoice(): void
    {
        $this->task->close(true);
        \Tk\Alert::addSuccess('The task has been invoiced and closed.');
        Uri::create()->remove('action')->redirect();
    }

    public function doCancel(): void
    {
        $this->task->cancel();
        \Tk\Alert::addSuccess('The task has been canceled.');
        Uri::create()->remove('action')->redirect();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        if ($this->task->taskId) {
            $template->setVisible('edit');
            $template->setText('modified', $this->task->modified->format(Date::FORMAT_LONG_DATETIME));
            $template->setText('created', $this->task->created->format(Date::FORMAT_LONG_DATETIME));

            $url = Uri::create('/taskLogManager')->set('taskId', $this->task->taskId);
            $template->setAttr('logs', 'href', $url);

            if ($this->task->status != Task::STATUS_OPEN) {
                $url = Uri::create()->set('action', 'open');
                $template->setAttr('re-open', 'href', $url);
                $template->setVisible('re-open');
            }

            if ($this->task->status == Task::STATUS_OPEN) {
                $url = Uri::create()->set('action', 'close');
                $template->setAttr('close', 'href', $url);
                $template->setVisible('close');

                $url = Uri::create()->set('action', 'close-invoice');
                $template->setAttr('close-invoice', 'href', $url);
                $template->setVisible('close-invoice');

                $url = Uri::create()->set('action', 'cancel');
                $template->setAttr('cancel', 'href', $url);
                $template->setVisible('cancel');
            }


            if ($this->task->isEditable()) {
                $url = Uri::create('/component/taskLogEditDialog')->set('taskId', $this->task->taskId);
                $template->setAttr('add-log', 'hx-get', $url);
                $template->setVisible('add-log');
            }
        }


        if ($this->task->getCost()->getAmount() != 0) {
            $template->setText('billable', "Billable: {$this->task->getCost()->toString()}");
            $template->setVisible('billable');
        }

        $template->appendTemplate('content', $this->form->show());

        if ($this->task->taskId) {
            $url = Uri::create('/component/taskLogTable')->set('taskId', $this->task->taskId);
            $template->setAttr('logTable', 'hx-get', $url);
            $template->setVisible('components');
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="row">
    <div class="col-md-12" choice="edit">
        <div class="page-actions card mb-3">
            <div class="card-body">
                <a title="Add a new Task Log" class="btn btn-outline-secondary" choice="add-log" data-toggle="modal"
                    hx-get="#"
                    hx-trigger="click queue:none"
                    hx-target="body"
                    hx-swap="beforeend">
                    <i class="fa fa-fw fa-plus"></i>
                    Add Log
                </a>
                <a href="#" title="Manage Task Logs" class="btn btn-outline-secondary" var="logs" choice="edit"><i class="fa fa-fw fa-tasks"></i> Task Logs</a>

                <div class="float-end">
                    <a href="#" title="Re-Open this task" class="btn btn-primary" choice="re-open" data-confirm="Are you sure you want to re-open this task?">Re-Open</a>
                    <a href="#" title="Close Task" class="btn btn-primary" choice="close" data-confirm="Are you sure you want to close this task">Close</a>
                    <a href="#" title="Invoice And Close Task" class="btn btn-success" choice="close-invoice" data-confirm="Are you sure you want to invoice then close this task"> Invoice</a>
                    <a href="#" title="Cancel Task" class="btn btn-warning" choice="cancel" data-confirm="Are you sure you want to cancel this task">Cancel</a>
                </div>
            </div>
        </div>
    </div>
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
          <div class="float-end me-2" choice="billable">Billable: $0.00</div>
        </div>
        <div class="card-body" var="content"></div>
      </div>
  </div>
  <div class="col-md-5" choice="components">
    <div hx-get="/component/taskLogTable" hx-trigger="load" hx-swap="outerHTML" var="logTable">
      <p class="text-center mt-4"><i class="fa fa-fw fa-spin fa-spinner fa-3x"></i><br>Loading...</p>
    </div>
  </div>

<script>
jQuery(function ($) {
    $(document).on('tkForm:afterSubmit', function() {
        location = location.href;
    });
});
</script>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}