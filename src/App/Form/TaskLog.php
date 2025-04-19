<?php
namespace App\Form;

use App\Form\Field\Minutes;
use Bs\Mvc\ControllerAdmin;
use Bs\Factory;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Exception;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Textarea;
use Tk\Form\Field\Hidden;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Uri;

/**
 * Example Controller:
 * <code>
 * class Edit extends \Bs\ControllerAdmin {
 *      protected ?\Bs\Form $form = null;
 *      public function doDefault(mixed $request, string $type): void
 *      {
 *          ...
 *          $this->form = new \Bs\Form\TaskLog($this->getTaskLog());
 *          $this->form->execute($_POST);
 *          ...
 *      }
 *      public function show(): ?Template
 *      {
 *          $template = $this->getTemplate();
 *          $template->appendTemplate('content', $this->form->show());
 *          return $template;
 *      }
 * }
 * </code>
 */
class TaskLog extends Form
{

    public function init(): static
    {
        $this->appendField(new Minutes('minutes'))
            ->setRequired();

        // TODO: implement a date time selector
        //$this->appendField(new Input('startAt', 'datetime'));
        $this->appendField(new Input('startAt', 'date'));

        $this->appendField((new Select('billable', ['' => '-- Select --', '1' => 'Yes', '0' => 'No']))
            ->setStrict(true)
        );

        $this->appendField(new Textarea('comment'))
            ->setAttr('data-elfinder-path', $this->getTaskLog()->dataPath . '/media')
            ->addCss('mce-min');

        $this->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->appendField(new Link('cancel', $this->getBackUrl()->set('taskId', $this->getTaskLog()->taskId)));

        return $this;
    }

    public function execute(array $values = []): static
    {
        $this->setCsrfTtl(0);
        $this->init();

        // Load form with object values
        $load = $this->getTaskLog()->unmapForm();
        $this->setFieldValues($load);

        parent::execute($values);
        return $this;
    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $values = $form->getFieldValues();
        $this->getTaskLog()->mapForm($values);

        $form->addFieldErrors($this->getTaskLog()->validate());
        if ($form->hasErrors()) {
            return;
        }

        $isNew = ($this->getTaskLog()->taskLogId == 0);
        $this->getTaskLog()->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create()->set('taskLogId', $this->getTaskLog()->taskLogId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Factory::instance()->getBackUrl()->set('taskId', $this->getTaskLog()->taskId));
        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        $this->getField('minutes')->addFieldCss('col-md-4');
        $this->getField('startAt')->addFieldCss('col-md-4');
        $this->getField('billable')->addFieldCss('col-md-4');

        $renderer = $this->getRenderer();

        return $renderer->show();
    }


    public function getTaskLog(): ?\App\Db\TaskLog
    {
        /** @var \App\Db\TaskLog $obj */
        $obj = $this->getModel();
        return $obj;
    }

}