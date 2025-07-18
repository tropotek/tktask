<?php
namespace App\Controller\TaskLog;

use App\Component\TaskLogEditDialog;
use App\Db\Task;
use App\Db\TaskLog;
use App\Db\User;
use App\Util\Tools;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Form\Field\Input;
use Tk\Table\Action\ColumnSelect;
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
            ->addOnHtml(function(TaskLog $obj, Cell $cell) {
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
            ->addCss('text-nowrap')
            ->addHeaderCss('max-width')
            ->setSortable(true);

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
            ->addHeaderCss('text-end')
            ->addCss('text-nowrap text-end')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::getLongDateTime');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');


        // Add Table actions
        $this->table->appendAction(ColumnSelect::create());
        $this->table->appendAction(Delete::createDefault(TaskLog::class, $rowSelect));
        $this->table->appendAction(Csv::createDefault(TaskLog::class, $rowSelect));

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
            $url = Uri::create('/component/taskLogEditDialog')->set('taskId', $this->task->taskId);
            $template->setAttr('add-log', 'hx-get', $url);
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
            <a title="Add a new Task Log" class="btn btn-outline-secondary" choice="add-log" data-toggle="modal"
                hx-get="#"
                hx-trigger="click queue:none"
                hx-target="body"
                hx-swap="beforeend">
                <i class="fa fa-fw fa-plus"></i>
                Add Log
            </a>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header">
            <i var="icon"></i> <span var="title"></span>
            <div class="float-end" choice="billable">Billable: $0.00</div>
        </div>
        <div class="card-body" var="content"></div>
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
        return Template::load($html);
    }

}