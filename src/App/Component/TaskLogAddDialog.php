<?php
namespace App\Component;

use App\Db\Invoice;
use App\Db\InvoiceItem;
use App\Db\Product;
use App\Db\Task;
use App\Db\TaskLog;
use App\Db\User;
use App\Form\Field\Datalist;
use App\Form\Field\Minutes;
use App\Form\Field\StatusSelect;
use Bs\Mvc\Form;
use Bs\Registry;
use Dom\Template;
use Tk\Collection;
use Tk\Db;
use Tk\Form\Action\Link;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Input;
use Tk\Form\Field\InputGroup;
use Tk\Form\Field\Select;
use Tk\Form\Field\Textarea;
use Tk\Log;
use Tk\Uri;

class TaskLogAddDialog extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    const string CONTAINER_ID = 'task-add-log-dialog';

    protected ?Form        $form     = null;
    protected array        $hxEvents = [];
    protected ?Task        $task     = null;
    protected ?TaskLog     $log      = null;


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $taskId = (int)($_POST['taskId'] ?? $_GET['taskId'] ?? 0);
        $this->task = Task::find($taskId);

        // use blank task if we do not get supplied a taskId
        if (is_null($this->task)) $this->task = new Task();

        $this->log = TaskLog::create($this->task);

        $this->form = new Form($this->task);
        $this->form->setAction('');
        $this->form->setAttr('hx-post', Uri::create('/component/taskLogAddDialog', compact('taskId')));
        $this->form->setAttr('hx-swap', 'outerHTML');
        $this->form->setAttr('hx-target', "#{$this->form->getId()}");
        $this->form->setAttr('hx-select', "#{$this->form->getId()}");

        if (Registry::instance()->get('site.invoice.enable', true)) {
            $products = Product::findFiltered(Db\Filter::create(['active' => true, 'productCategoryId' => Product::LABOR_CAT_ID]));
            $list = Collection::toSelectList($products, 'productId');
            $this->form->appendField((new Select('productId', $list))
                ->prependOption('-- Select --', ''));
        }

        $this->form->appendField(new Minutes('minutes'))->setLabel('Time Worked');

        // todo This should be a date-time field
        $this->form->appendField(new Input('startAt', 'date'));

        $this->form->appendField((new Select('billable', ['' => '-- Select --', '1' => 'Yes', '0' => 'No']))
            ->setStrict(true)
        );

        $list = \App\Db\Task::STATUS_LIST;
        $this->form->appendField(new StatusSelect('status', $list));

        $this->form->appendField(new Textarea('comment'))->addCss('mce-min');

        $this->form->appendField(new Link('cancel', Uri::create('#')))
            ->setAttr('data-bs-dismiss', 'modal')
            ->addCss('float-end');
        $this->form->appendField(new Submit('save', [$this, 'onSubmit']))
            ->addCss('float-end');

        $load = $this->log->unmapForm();
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

        // Send HX event headers
        if (count($this->hxEvents)) {
            header(sprintf('HX-Trigger: %s', json_encode($this->hxEvents)));
        }

        return $this->show();
    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $values = $form->getFieldValues();
        $this->log->mapForm($values);

        if (!Registry::instance()->get('site.invoice.enable', true)) {
            $this->log->productId = Product::getDefaultLaborProduct()->productId;
        }

        if (!$form->getFieldValue('comment') && $form->getFieldValue('status') != \App\Db\Task::STATUS_CLOSED) {
            $form->addFieldError('comment', 'Please add a comment so we know what happened.');
        }

        if ($form->getFieldValue('billable') === '') {
            $form->addFieldError('billable', 'Select a billable status');
        }

        $form->addFieldErrors($this->log->validate());
        if ($form->hasErrors()) {
            $this->hxEvents['tkForm:onError'] = ['status' => 'err', 'errors' => $form->getAllErrors()];
            return;
        }

        $this->task->addTaskLog($this->log, trim($_POST['status_msg'] ?? ''), truefalse($_POST['status_notify'] ?? false));

        // Trigger HX events
        $this->hxEvents['tkForm:afterSubmit'] = ['status' => 'ok'];
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('dialog', 'id', $this->getDialogId());

        $this->form->getField('minutes')->addFieldCss('col-4');
        $this->form->getField('startAt')->addFieldCss('col-4');
        $this->form->getField('billable')->addFieldCss('col-4');

        $this->form->getRenderer()->getTemplate()->addCss('actions', 'mt-4 float-end');

        if ($this->form) {
            $this->form->getRenderer()->getTemplate()->removeCss('fields', 'g-3 mt-1')->addCss('fields', 'g-2');
            $template->appendTemplate('content', $this->form->show());
        }

        return $template;
    }

    public function getDialogId(): string
    {
        return self::CONTAINER_ID;
    }

    public function __makeTemplate(): ?Template
    {
        $baseUrl = Uri::create('/component/taskLogAddDialog')->toString();
        $editUrl = Uri::create('/taskLogEdit', ['taskLogId' => $this->log->taskLogId])->toString();

        $html = <<<HTML
<div class="modal fade in" data-bs-backdrop="static" var="dialog" aria-hidden="false">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="add-log-title">Add Task Log: </h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" var="content"></div>
    </div>
  </div>

</div>

<script>
jQuery(function($) {
    const dialog    = '#{$this->getDialogId()}';
    const form      = '#{$this->form->getId()}';
    const baseUrl   = '$baseUrl';

    // reload page after successfull submit
    $(document).on('htmx:afterSettle', function(e) {
        if (!$(e.detail.elt).is(form)) return;
        if (e.detail.requestConfig.verb === 'get') {
            tkInit(form);
        }
    });

    $(document).on('htmx:beforeRequest', function(e) {
        if ($(e.detail.elt).is(form) && e.detail.requestConfig.verb === 'post' && tinymce.activeEditor) {
            // set the description value as tinymce is not in the HTMX dom tree
            e.detail.requestConfig.parameters['comment'] = tinymce.activeEditor.getContent();
        }
    });

    // reload page after successfull submit
    $(document).on('tkForm:afterSubmit', function(e) {
        if (!$(e.detail.elt).is(form)) return;
        $(dialog).modal('hide');
    });

    // reset form values
    $(dialog).on('show.bs.modal', function(e) {
        htmx.ajax('get', baseUrl, {
            source:    form,
            target:    form,
            swap:      'outerHTML',
            values:    {
                taskId: $(e.relatedTarget).data('taskId')
            },
        });
    });
});
</script>
HTML;
        return Template::load($html);
    }

}
