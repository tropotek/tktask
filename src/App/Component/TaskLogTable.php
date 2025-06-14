<?php
namespace App\Component;

use App\Db\Task;
use App\Db\TaskLog;
use App\Db\User;
use App\Util\Tools;
use Bs\Mvc\ComponentInterface;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Db;
use Tk\Table\Action\Csv;
use Tk\Table\Cell;
use Tk\Uri;

class TaskLogTable extends \Dom\Renderer\Renderer implements ComponentInterface
{
    protected Table $table;
    protected ?Task $task = null;


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $taskId = (int)($_REQUEST['taskId'] ?? 0);

        $this->task = Task::find($taskId);
        if (!$this->task) return null;

        // init table
        $this->table = new Table('table-task-log');
        $this->table->setOrderBy('-created');
        $this->table->setLimit(10);
        $this->table->addCss('tk-table-sm');

        $this->table->appendCell('actions')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(\App\Db\TaskLog $obj, Cell $cell) {

                $disabled = $obj->status != Task::STATUS_OPEN ? 'disabled' : '';
                $url = Uri::create('/component/taskLogEditDialog')->set('taskLogId', $obj->taskLogId);
                $id = '#'.TaskLogEditDialog::CONTAINER_ID;
                return <<<HTML
                    <button class="btn btn-primary $disabled" title="Edit Task Log" $disabled
                        hx-get="{$url}"
                        hx-select="{$id}"
                        hx-trigger="click queue:none"
                        hx-target="body"
                        hx-swap="beforeend">
                        <i class="fas fa-fw fa-pencil-alt"></i>
                    </button>
                HTML;
            });

        $this->table->appendCell('comment')
            ->addHeaderCss('max-width')
            ->addOnValue(function(TaskLog $obj, Cell $cell) {
                $cell->setAttr('title', 'User: ' . $obj->getUser()->nameShort);
                return $obj->comment;
            });

        $this->table->appendCell('minutes')
            ->setHeader('Duration')
            ->addCss('text-nowrap')
            ->setAttr('title', 'hh:mm')
            ->addOnValue(function(TaskLog $obj, Cell $cell) {
                return Tools::mins2Str($obj->minutes);
            });

        $this->table->appendCell('startAt')
            ->setHeader('Started')
            ->addCss('text-nowrap text-center')
            ->addOnValue('\Tk\Table\Type\DateTime::onValue');

        $this->table->appendCell('billable')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        // execute table
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $filter['taskId'] = $this->task->taskId;
        $rows = TaskLog::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());

        return $this->show();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $this->table->getRenderer()->setMaxPages(3);
        $template->appendTemplate('content', $this->table->htmxShow());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div id="task-log-table">
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-cogs"></i> <span var="title">Task Logs</span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}
