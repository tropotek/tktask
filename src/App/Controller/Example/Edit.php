<?php
namespace App\Controller\Example;

use App\Db\Example;
use Bs\ControllerAdmin;
use Bs\Db\Permissions;
use Bs\Form;
use Dom\Template;
use Tk\Alert;
use Tk\Exception;
use Tk\Form\Action\Link;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\File;
use Tk\Form\Field\Hidden;
use Tk\Form\Field\Input;
use Tk\Form\Field\Textarea;
use Tk\Uri;
use Tt\DataMap\Form\Boolean;

class Edit extends ControllerAdmin
{

    protected ?Example $example = null;
    protected ?Form    $form    = null;

    public function doDefault()
    {
        $this->getPage()->setTitle('Edit Example');
        $this->setAccess(Permissions::PERM_ADMIN);


        $exampleId = intval($_GET['exampleId'] ?? 0);
        $this->example = new Example();
        if ($exampleId) {
            $this->example = Example::find($exampleId);
            if (!$this->example) {
                throw new Exception("Invalid Example ID: $exampleId");
            }
        }

        $this->form = new Form($this->example);

        // init form fields
        $group = 'left';
        $this->form->appendField(new Hidden('exampleId'))->setGroup($group);
        $this->form->appendField(new Input('name'))->setGroup($group)->setRequired();

        /** @var File $image */
        $image = $this->form->appendField(new File('image'))->setGroup($group);
        if ($this->example->image) {
            $image->setViewUrl($this->getConfig()->getDataUrl() . $this->example->image);
            $image->setDeleteUrl(Uri::create()->set('del-image', $this->example->exampleId));
        }

        $this->form->appendField(new Checkbox('active', ['Enable Example' => 'active']))
            ->setDataType(Boolean::class)->setGroup($group);

        $this->form->appendField(new Textarea('notes'))->setGroup($group)->setValue('This is a test');


        // Form Actions
        $this->form->appendField(new SubmitExit('save', [$this, 'onSubmit']));
        $this->form->appendField(new Link('cancel', $this->getFactory()->getBackUrl()));

        // Load form with object values
        $values = $this->form->unmapValues($this->example);
        $this->form->setFieldValues($values);

        // Execute form with request values
        $this->form->execute($_POST);

    }

    public function onSubmit(Form $form, SubmitExit $action): void
    {
        $form->mapValues($this->example);      // set object values from fields

        $form->addFieldErrors($this->example->validate());
        if ($form->hasErrors()) {
            return;
        }

        /** @var Form\Field\File $fileOne */
        $image = $form->getField('image');
        if ($image->hasFile()) {
            if ($this->example->image) {    // Delete any existing file
                unlink($this->getConfig()->getDataPath() . $this->example->image);
            }
            $filepath = $image->move($this->getConfig()->getDataPath() . $this->example->getDataPath());
            $filepath = str_replace($this->getConfig()->getDataPath(), '', $filepath);
            $this->example->image = $filepath;
        }

        $this->example->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create()->set('exampleId', $this->example->exampleId));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect($this->getFactory()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $this->form->getField('name')->addFieldCss('col-6');
        $this->form->getField('image')->addFieldCss('col-6');
        $this->form->getRenderer()->addFieldCss('mb-3');

        $template->appendTemplate('content', $this->form->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-cogs"></i> </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}