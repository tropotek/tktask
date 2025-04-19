<?php
namespace App\Form;

use App\Db\Company;
use App\Db\StatusLog;
use App\Db\User;
use App\Form\Field\StatusSelect;
use Bs\Mvc\ControllerAdmin;
use Bs\Factory;
use Bs\Mvc\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Collection;
use Tk\Db\Filter;
use Tk\Exception;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Action\Submit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\InputGroup;
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
 *          $this->form = new \Bs\Form\Project($this->getProject());
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
class Project extends Form
{

    public function init(): static
    {
        $this->appendField(new Input('name'))
            ->setRequired();

        $cats = Company::findFiltered(Filter::create(['active' => true, 'type' => Company::TYPE_CLIENT], 'name'));
        $list = Collection::toSelectList($cats, 'companyId');
        $this->appendField((new Select('companyId', $list))
            ->prependOption('-- Company --', ''))
        ->setRequired();

        $cats = User::findFiltered(Filter::create(['active' => true, 'type' => User::TYPE_STAFF], 'name_short'));
        $list = Collection::toSelectList($cats, 'userId', 'nameShort');
        $this->appendField((new Select('userId', $list))
            ->prependOption('-- Select --', '')
            ->setLabel('Lead')
            ->setRequired()
        );

        $this->appendField(new InputGroup('quote', '$'));

        $this->appendField(new Input('startOn', 'date'));

        $this->appendField(new Input('endOn', 'date'));

        $list = \App\Db\Project::STATUS_LIST;
        $this->form->appendField(new StatusSelect('status', $list));

        $this->appendField(new Textarea('description'))->addCss('mce-min');
        //$this->appendField(new Textarea('notes'));

        $this->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->appendField(new Link('cancel', Factory::instance()->getBackUrl()));

        return $this;
    }

    public function execute(array $values = []): static
    {
        $this->setCsrfTtl(0);
        $this->init();

        // Load form with object values
        $load = $this->getProject()->unmapForm();
        $this->setFieldValues($load);

        parent::execute($values);
        return $this;
    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $values = $form->getFieldValues();
        $this->getProject()->mapForm($values);

        $form->addFieldErrors($this->getProject()->validate());
        if ($form->hasErrors()) {
            return;
        }

        $isNew = ($this->getProject()->projectId == 0);
        $this->getProject()->save();

        StatusLog::create($this->getProject(), trim($_POST['status_msg'] ?? ''), truefalse($_POST['status_notify'] ?? false));

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create()->set('projectId', $this->getProject()->projectId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Factory::instance()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        $this->getField('name')->addFieldCss('col-md-6');
        $this->getField('companyId')->addFieldCss('col-md-6');
        $this->getField('userId')->addFieldCss('col-md-6');
        $this->getField('quote')->addFieldCss('col-md-6');
        $this->getField('startOn')->addFieldCss('col-md-6');
        $this->getField('endOn')->addFieldCss('col-md-6');

        $renderer = $this->getRenderer();

        // set end date min range to start date
        $js = <<<JS
jQuery(function($) {
    $('#project_startOn').on('change', function() {
        $('#project_endOn').attr('min', $(this).val());
    }).trigger('change');
});
JS;
        $renderer->getTemplate()->appendJs($js);

        return $renderer->show();
    }


    public function getProject(): ?\App\Db\Project
    {
        /** @var \App\Db\Project $obj */
        $obj = $this->getModel();
        return $obj;
    }

}