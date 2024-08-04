<?php
namespace App\Controller\Widget;

use App\Db\Widget;
use Bs\ControllerDomInterface;
use Bs\Db\User;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Exception;
use Tk\Form;
use Tk\Uri;
use Tt\DataMap\Form\Boolean;
use Tt\DataMap\Form\Date;

class Edit extends ControllerDomInterface
{
    protected ?Widget $widget = null;
    protected ?Form   $form   = null;


    public function doDefault(Request $request): void
    {
        $this->getPage()->setTitle('Edit Widget');
        $this->setAccess(User::PERM_ADMIN);

        $widgetId = intval($_GET['widgetId'] ?? 0);
        $this->widget = new Widget();
        if ($widgetId) {
            $this->widget = Widget::get($widgetId);
        }

        if (!$this->widget) {
            throw new Exception('Invalid widget id: ' . $widgetId);
        }

        $this->form = Form::create('test');

        $this->form->appendField(new Form\Field\Input('name'));
        $this->form->appendField(new Form\Field\Checkbox('active'))->setDataType(Boolean::class);
        $this->form->appendField(new Form\Field\Input('date'))->addCss('date')->setDataType(Date::class);
        $this->form->appendField(new Form\Field\Input('year'))->setDataType(Date::class);


        $list = [
            '-- Select --' => '',
            'Core' => 'core',
            'Elective Hospital' => 'elective_hospital',
            'Elective Online' => 'elective_online',
            'CSC' => 'csc',
        ];
        $this->form->appendField(new Form\Field\Select('enumType', $list))
            ->setNotes('This is a select box');

        $this->form->appendField(new Form\Field\Textarea('notes'));

        // Form Actions
        $this->form->appendField(new Form\Action\SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Form\Action\Link('cancel', $this->getFactory()->getBackUrl()));

        // Load form with object values
        $values = $this->form->unmapValues($this->widget);
        $this->form->setFieldValues($values);

        // Execute form with request values
        $this->form->execute($request->request->all());

    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action): void
    {
        $form->mapValues($this->widget);      // set object values from fields

        // validate values
        if (!$this->widget->name) {
            $form->addFieldError('name', "invalid field value");
        }

        // validate object with method
        //$form->addFieldErrors($this->widget->validate());

        if ($form->hasErrors()) {
            Alert::addError('Form contains errors.');
            return;
        }

        $isNew = $this->widget->widgetId == 0;
        $this->widget->save();

        // Update other params

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create('/widgetEdit')->set('widgetId', $this->widget->widgetId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect($this->getFactory()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        // Standard text/php renderer
//        $formRenderer = new Form\Renderer\Std\Renderer($this->form);
//        $template->appendHtml('form', $formRenderer->show());

        // DomTemplate renderer
        $formRenderer = new Form\Renderer\Dom\Renderer($this->form);
        $template->appendTemplate('form', $formRenderer->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-cogs"></i> </div>
    <div class="card-body" var="form"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}