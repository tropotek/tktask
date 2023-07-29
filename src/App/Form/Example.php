<?php
namespace App\Form;

use Bs\Form\EditInterface;
use Dom\Template;
use Tk\Alert;
use Tk\Db\Mapper\Model;
use Tk\Form;
use Tk\Uri;
use Tk\Form\Field;
use Tk\Form\Action;

class Example extends EditInterface
{

    public function initFields(): void
    {
        // init form fields
        $group = 'left';
        $this->appendField(new Field\Hidden('exampleId'))->setGroup($group);
        $this->appendField(new Field\Input('name'))->setGroup($group)->setRequired();

        /** @var Field\File $image */
        $image = $this->appendField(new Field\File('image'))->setGroup($group);
        if ($this->getExample()->getImage()) {
            $image->setViewUrl($this->getConfig()->getDataUrl() . $this->getExample()->getImage());
            $image->setDeleteUrl(Uri::create()->set('del-image', $this->getExample()->getId()));
        }

        //$fileList = $this->appendField(new \Bs\Form\Field\File('fileList', $this->ex))->setGroup($group);

        $this->appendField(new Field\Checkbox('active', ['Enable Example' => 'active']))->setGroup($group);
        //$this->appendField(new Field\Textarea('content'))->setGroup($group);
        $this->appendField(new Field\Textarea('notes'))->setGroup($group);

        $this->appendField(new Action\SubmitExit('save', [$this, 'onSubmit']));
        $this->appendField(new Action\Link('cancel', Uri::create('/exampleManager')));

    }

    public function execute(array $values = []): static
    {
        $load = $this->getExample()->getMapper()->getFormMap()->getArray($this->getExample());
        $load['exampleId'] = $this->getExample()->getExampleId();
        $this->setFieldValues($load);
        parent::execute($values);
        return $this;
    }

    public function onSubmit(Form $form, Action\ActionInterface $action): void
    {
        $this->getExample()->getMapper()->getFormMap()->loadObject($this->getExample(), $form->getFieldValues());

        // TODO: validate file ???

        $form->addFieldErrors($this->getExample()->validate());
        if ($form->hasErrors()) {
            return;
        }

        /** @var Form\Field\File $fileOne */
        $image = $form->getField('image');
        if ($image->hasFile()) {
            if ($this->getExample()->getImage()) {    // Delete any existing file
                unlink($this->getConfig()->getDataPath() . $this->getExample()->getImage());
            }
            $filepath = $image->move($this->getConfig()->getDataPath() . $this->getExample()->getDataPath());
            $filepath = str_replace($this->getConfig()->getDataPath(), '', $filepath);
            $this->getExample()->setImage($filepath);
        }

        $this->getExample()->save();

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create()->set('exampleId', $this->getExample()->getExampleId()));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect($this->getFactory()->getBackUrl());
        }

//        if (!$form->getRequest()->headers->has('HX-Request')) {
//            $action->setRedirect(Uri::create('/exampleEdit/'.$this->getExample()->getExampleId()));
//        }
    }

    public function show(): ?Template
    {
        $renderer = $this->getFormRenderer();
        $this->getField('name')->addFieldCss('col-6');
        $this->getField('image')->addFieldCss('col-6');
        $renderer->addFieldCss('mb-3');
        return $renderer->show();
    }

    public function getExample(): \App\Db\Example|Model
    {
        return $this->getModel();
    }

}