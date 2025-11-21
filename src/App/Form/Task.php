<?php
namespace App\Form;

use App\Db\Company;
use App\Db\TaskCategory;
use App\Db\User;
use App\Form\Field\Minutes;
use Bs\Mvc\Form;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Alert;
use Tk\Collection;
use Tk\Db\Filter;
use Tk\Form\Action\Link;
use Tk\Form\Action\Submit;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Field\Html;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Form\Field\Textarea;
use Tk\Uri;

/**
 * Example Controller:
 * <code>
 * class Edit extends \Bs\ControllerAdmin {
 *      protected ?\Bs\Form $form = null;
 *      public function doDefault(mixed $request, string $type): void
 *      {
 *          ...
 *          $this->form = new \Bs\Form\Task($this->getTask());
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
class Task extends Form
{

    public function init(): static
    {

        // show project select for new task only if open tasks exists
        if ($this->getTask()->taskId == 0) {
            $projects = \App\Db\Project::findFiltered(Filter::create(['status' => \App\Db\Project::STATUS_ACTIVE], '-created'));
            if (count($projects)) {
                $list = Collection::toSelectList($projects, 'projectId');
                $this->form->appendField((new Select('projectId', $list))
                    ->prependOption('-- Select --', '')
                );
            }
        } else {
            // show project readonly field
            if ($this->getTask()->projectId) {
                $html = $this->getTask()->getProject()->name;
                $this->form->appendField(new Html('projectId', $html));
            }
        }

        $this->appendField(new Input('subject'))
            ->addFieldCss('col-md-8')
            ->setRequired();

        $categories = TaskCategory::findFiltered(Filter::create(['active' => true], 'order_by'));
        $list = Collection::toSelectList($categories, 'taskCategoryId', 'name');
        $this->appendField((new Select('taskCategoryId', $list))
            ->prependOption('-- Select --', '')
            ->addFieldCss('col-md-4')
            ->setRequired()
        );

        $companies = Company::findFiltered(Filter::create(['type' => Company::TYPE_CLIENT, 'active' => true], 'name'));
        $list = Collection::toSelectList($companies, 'companyId');
        $fld = $this->appendField((new Select('companyId', $list))
            ->setLabel('Client')
            ->prependOption($this->getTask()->companyName, $this->getTask()->companyId, true)
            ->prependOption('-- Select --', '')
            ->addFieldCss('col-md-6')
            ->setRequired()
        );

        if ($this->getTask()->taskId != 0) {
            $fld->setDisabled();
        }

        $users = User::findFiltered(Filter::create(['active' => true, 'type' => User::TYPE_STAFF], 'name_short'));
        $list = Collection::toSelectList($users, 'userId', 'nameShort');
        $this->appendField((new Select('assignedUserId', $list))
            ->prependOption($this->getTask()->assignedName, $this->getTask()->assignedUserId, true)
            ->prependOption('-- Select --', '')
            ->addFieldCss('col-md-6')
            ->setRequired()
        );

        $this->appendField(new Minutes('minutes'))
            ->setLabel('Est. Duration')
            ->addFieldCss('col');

        $this->appendField((new Select('priority', \App\Db\Task::PRIORITY_LIST))
            ->prependOption('-- Select --', '')
            ->addFieldCss('col')
        );

        if ($this->getTask()->taskId) {
            $this->appendField(new Html('status', $this->getTask()->status))
                ->setDisabled()
                ->addCss('form-control disabled')
                ->addFieldCss('col');
        }

        $this->appendField(new Textarea('comments'))
            ->setAttr('data-elfinder-path', $this->getTask()->dataPath . '/media')
            ->addCss('mce-min');

        $this->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->appendField(new Link('cancel', Breadcrumbs::getBackUrl()));

        return $this;
    }

    public function execute(array $values = []): static
    {
        $this->setCsrfTtl(0);
        $this->init();

        // Load form with object values
        $load = $this->getTask()->unmapForm();
        $this->setFieldValues($load);

        parent::execute($values);
        return $this;
    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $values = $form->getFieldValues();
        $this->getTask()->mapForm($values);

        $form->addFieldErrors($this->getTask()->validate());
        if ($form->hasErrors()) {
            return;
        }
        $isNew = ($this->getTask()->taskId == 0);
        $this->getTask()->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create()->set('taskId', $this->getTask()->taskId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Breadcrumbs::getBackUrl());
        }
    }

    public function show(): ?Template
    {
        $renderer = $this->getRenderer();

        return $renderer->show();
    }


    public function getTask(): ?\App\Db\Task
    {
        /** @var \App\Db\Task $obj */
        $obj = $this->getModel();
        return $obj;
    }

}