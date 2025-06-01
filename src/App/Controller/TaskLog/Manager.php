<?php
namespace App\Controller\TaskLog;

use App\Db\Task;
use App\Db\TaskLog;
use App\Db\User;
use App\Util\Tools;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Alert;
use Tk\Form\Field\Input;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;
use Tk\Table\Action\Csv;
use Tk\Table\Action\Delete;
use Tk\Uri;
use Tk\Db;

class Manager extends ControllerAdmin
{
    protected ?Table $table = null;
    protected ?Task  $task  = null;

    public function doDefault(): void
    {
        $taskId = intval($_REQUEST['taskId'] ?? 0);
        $this->task = Task::find($taskId);

        $this->getPage()->setTitle('Task Log Manager', 'fas fa-tasks');
        $this->validateAccess(User::getAuthUser()?->isStaff() ?? false);

        // init table
        $this->table = new Table();
        $this->table->setOrderBy('-created');
        $this->table->setLimit(25);

        $rowSelect = RowSelect::create('id', 'taskLogId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('actions')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(TaskLog $obj, Cell $cell) {
                $url = Uri::create('/taskLogEdit')->set('taskLogId', $obj->taskLogId);
                return <<<HTML
                    <a class="btn btn-outline-success" href="$url" title="Edit"><i class="fa fa-fw fa-edit"></i></a>
                HTML;
            });

        $this->table->appendCell('comment')
            ->addCss('text-nowrap')
            ->addHeaderCss('max-width')
            ->setSortable(true)
            ->addOnValue(function(TaskLog $obj, Cell $cell) {
                return $obj->comment;
            });

//        $this->table->appendCell('userId')
//            ->addCss('text-nowrap')
//            ->setSortable(true)
//            ->addOnValue(function(TaskLog $obj, Cell $cell) {
//                return $obj->getUser()?->nameShort ?? 'N/A';
//            });

        $this->table->appendCell('status')
            ->addCss('text-nowrap')
            ->setSortable(true);

        $this->table->appendCell('billable')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        $this->table->appendCell('productId')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue(function(\App\Db\TaskLog $obj, Cell $cell) {
                return $obj->getProduct()->name ?? 'N/A';
            });

        $this->table->appendCell('minutes')
            ->setHeader('Duration')
            ->addCss('text-nowrap')
            ->setAttr('title', 'hh:mm')
            ->setSortable(true)
            ->addOnValue(function(\App\Db\TaskLog $obj, Cell $cell) {
                return Tools::mins2Str($obj->minutes);
            });

        $this->table->appendCell('created')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\DateTime::onValue');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');


        // Add Table actions
        if($this->task->isEditable()) {
            $this->table->appendAction(Delete::create()
                ->addOnGetSelected([$rowSelect, 'getSelected'])
                ->addOnDelete(function (Delete $action, array $selected) {
                    foreach ($selected as $task_log_id) {
                        Db::delete('task_log', compact('task_log_id'));
                    }
                }));
        }

        $this->table->appendAction(Csv::create()
            ->addOnCsv(function(Csv $action) {
                $action->setExcluded(['actions']);
                if (!$this->table->getCell(TaskLog::getPrimaryProperty())) {
                    $this->table->prependCell(TaskLog::getPrimaryProperty())->setHeader('id');
                }
                $filter = $this->table->getDbFilter()->resetLimits();
                return TaskLog::findFiltered($filter);
            }));

        // execute table
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        if($taskId) {
            $filter->set('taskId', $taskId);
        }
        $rows = TaskLog::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        if ($this->task->isEditable()) {
            $url = Uri::create('/taskLogEdit')->set('taskId', $this->task->taskId);
            $template->setAttr('create', 'href', $url);
            $template->setVisible('add-log');
        }

        $template->appendTemplate('content', $this->table->show());

        if ($this->task->getCost()->getAmount() != 0) {
            $template->setText('billable', "Billable: {$this->task->getCost()->toString()}");
            $template->setVisible('billable');
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3" choice="add-log">
    <div class="card-body">
      <a href="#" title="Create Task Log" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Add Log</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header">
        <i var="icon"></i> <span var="title"></span>
        <div class="float-end" choice="billable">Billable: $0.00</div>
    </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}