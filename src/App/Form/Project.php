<?php
namespace App\Form;

use App\Db\Company;
use App\Db\User;
use Bs\Mvc\Form;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Alert;
use Tk\Collection;
use Tk\Db\Filter;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Action\Submit;
use Tk\Form\Field\InputGroup;
use Tk\Form\Field\Textarea;
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
            ->addFieldCss('col-md-6')
            ->setRequired();

        $cats = Company::findFiltered(Filter::create(['active' => true, 'type' => Company::TYPE_CLIENT], 'name'));
        $list = Collection::toSelectList($cats, 'companyId');
        $this->appendField((new Select('companyId', $list))
            ->prependOption('-- Company --', ''))
            ->addFieldCss('col-md-6')
            ->setRequired();

        $cats = User::findFiltered(Filter::create(['active' => true, 'type' => User::TYPE_STAFF], 'name_short'));
        $list = Collection::toSelectList($cats, 'userId', 'nameShort');
        $this->appendField((new Select('userId', $list))
            ->prependOption('-- Select --', '')
            ->setLabel('Lead')
            ->addFieldCss('col-md-6')
            ->setRequired()
        );

        $this->appendField(new InputGroup('quote', '$'))
            ->addFieldCss('col-md-6');

        $this->appendField(new Input('startOn', 'date'))
            ->addFieldCss('col-md-6')
            ->setRequired();

        $this->appendField(new Input('endOn', 'date'))
            ->addFieldCss('col-md-6')
            ->setRequired();

        $this->appendField(new Textarea('description'))
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

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create()->set('projectId', $this->getProject()->projectId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Breadcrumbs::getBackUrl());
        }
    }

    public function show(): ?Template
    {
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