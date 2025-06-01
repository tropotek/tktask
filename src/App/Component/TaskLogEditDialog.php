<?php
namespace App\Component;

use App\Db\Product;
use App\Db\Task;
use App\Db\TaskLog;
use App\Db\User;
use App\Form\Field\Minutes;
use Bs\Mvc\Form;
use Bs\Registry;
use Dom\Template;
use Tk\Collection;
use Tk\Db;
use Tk\Exception;
use Tk\Form\Action\Link;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Form\Field\Textarea;
use Tk\Log;
use Tk\Uri;

class TaskLogEditDialog extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    const string CONTAINER_ID = 'task-edit-log-dialog';

    protected ?Form        $form       = null;
    protected ?Task        $task       = null;
    protected ?TaskLog     $log        = null;
    protected array        $hxTriggers = [];


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $taskId = (int)($_REQUEST['taskId'] ?? 0);
        $taskLogId = (int)($_REQUEST['taskLogId'] ?? 0);

        if ($taskLogId) {
            $this->log = TaskLog::find($taskLogId);
            if (!($this->log instanceof TaskLog)) {
                Log::warning("task log not found with id $taskLogId");
                return null;
            }
        } elseif ($taskId) {
            $this->log = new TaskLog();
            $this->log->taskId = $taskId;
        } else {
            Log::warning("task log id or task id not supplied");
            return null;
        }

        $this->task = $this->log->getTask();

        $this->form = new Form($this->task, 'form-task-log-edit');
        $this->form->setAction('');
        $this->form->setAttr('hx-post', Uri::create(null, compact('taskId')));
        $this->form->setAttr('hx-swap', 'outerHTML');
        $this->form->setAttr('hx-target', "#{$this->form->getId()}");
        $this->form->setAttr('hx-select', "#{$this->form->getId()}");

        if (Registry::getValue('site.invoice.enable', true)) {
            $products = Product::findFiltered(Db\Filter::create(['active' => true, 'productCategoryId' => Product::LABOR_CAT_ID]));
            $list = Collection::toSelectList($products, 'productId');
            $this->form->appendField((new Select('productId', $list))
                ->prependOption('-- Select --', ''));
        }

        if ($this->log->taskLogId == 0) {
            $list = [
                'open' => 'Open',
                'close' => 'Close',
                'close-invoice' => 'Invoice',
                'cancel' => 'Cancel',
            ];
            $this->form->appendField((new Select('taskAction', $list))
                ->setStrict(true)
                ->addFieldCss('col-md-6')
                ->setNotes('Set the task action after saving the log')
            );
        }

        $this->form->appendField((new Select('billable', ['' => '-- Select --', '1' => 'Yes', '0' => 'No']))
            ->setStrict(true)
            ->addFieldCss('col-md-6')
        );

        $this->form->appendField(new Minutes('minutes'))
            ->setLabel('Time Worked')
            ->addFieldCss('col-md-6')
            ->setRequired();

        // todo This should be a date-time field
        $this->form->appendField(new Input('startAt', 'date'))
            ->addFieldCss('col-md-6');

        $this->form->appendField(new Textarea('comment'))
            ->addCss('mce-min')
            ->setAttr('data-elfinder-path', $this->task->dataPath . '/media')
            ->setRequired();

        $this->form->appendField(new Link('cancel', Uri::create('#')))
            ->setAttr('data-bs-dismiss', 'modal')
            ->addCss('float-end');
        $this->form->appendField(new Submit('save', [$this, 'onSubmit']))
            ->addCss('float-end');

        $load = $this->log->unmapForm();
        $this->form->setFieldValues($load);

        $this->form->execute($_POST);

        if (!$this->form->isSubmitted()) {
            // IMPORTANT: This component always sets the htmx target and swap to end of the surrounding page <body>.
            // That ignores hx-target and hx-swap in the triggering element, which you can omit.
            header('HX-Retarget: body');
            header('HX-Reswap: beforeend');
        }

        // Send HX event headers
        if (count($this->hxTriggers)) {
            header(sprintf('HX-Trigger: %s', json_encode($this->hxTriggers)));
        }

        return $this->show();
    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $values = $form->getFieldValues();
        $this->log->mapForm($values);

        if (!Registry::getValue('site.invoice.enable', true)) {
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
            $this->hxTriggers['tkForm:onError'] = ['status' => 'err', 'errors' => $form->getAllErrors()];
            return;
        }

        $this->log->save();

        $task = $this->log->getTask();
        if (is_null($task)) {
            throw new Exception('Task not found');
        }

        match ($values['taskAction'] ?? '') {
            'close'         => $task->close(),
            'close-invoice' => $task->close(true),
            'cancel'        => $task->cancel(),
            default         => null,
        };

        // Trigger HX events
        $this->hxTriggers['tkForm:afterSubmit'] = ['status' => 'ok'];
        $this->hxTriggers['tkForm:dialogclose'] = '#'.self::CONTAINER_ID;
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('dialog', 'id', $this->getDialogId());

        $this->form->getRenderer()->getTemplate()->addCss('actions', 'mt-4 float-end');
        $this->form->getRenderer()->getTemplate()->removeCss('fields', 'g-3 mt-1')->addCss('fields', 'g-2');
        $template->appendTemplate('content', $this->form->show());

        return $template;
    }

    public function getDialogId(): string
    {
        return self::CONTAINER_ID;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="modal fade in" data-bs-backdrop="static" tabindex="-1" aria-hidden="true" var="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" var="title">Edit Task Log</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" var="content"></div>
        </div>
    </div>
    <script>
        jQuery(function($) {
            const dialog    = '#{$this->getDialogId()}';
            const form      = '#{$this->form->getId()}';

            $(document).on('htmx:afterSettle', function(e) {
                if ($(e.detail.elt).is(form)) tkInit(form);
            });

            $(document).on('htmx:beforeRequest', function(e) {
                if ($(e.detail.elt).is(form) && e.detail.requestConfig.verb === 'post') {
                    // set the description value as tinymce is not in the HTMX dom tree
                    e.detail.requestConfig.parameters['comment'] = tinymce.activeEditor.getContent();
                }
            });

            // open the dialog as soon as HTMX settles
            tkInit(form);
            $(dialog).modal('show');

            // put focus field when dialog shows
            $(dialog).on('shown.bs.modal', function() {
                setTimeout(function() { $('[name=comment]', dialog).focus(); }, 0);
            });

            // catch dialog finished handling post request
            $('body').on('tkForm:dialogclose', function(e) {
                $(dialog).modal('hide');
            });

            // remove the dialog element from the dom when it closes
            $(dialog).on('hidden.bs.modal', function() {
                $(dialog).remove();
            });
        });
    </script>
</div>
HTML;

        return Template::load($html);
    }

}
