<?php
namespace App\Form;

use App\Db\ExampleMap;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Exception;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Action;
use Tk\FormRenderer;
use Tk\Traits\SystemTrait;
use Tk\Uri;

class Example
{
    use SystemTrait;
    use Form\FormTrait;

    protected ?\App\Db\Example $ex = null;


    public function __construct()
    {
        $this->setForm(Form::create('example'));
    }

    public function doDefault(Request $request, $id)
    {
        $this->ex = new \App\Db\Example();
        if ($id) {
            $this->ex = ExampleMap::create()->find($id);
            if (!$this->ex) {
                throw new Exception('Invalid User ID: ' . $id);
            }
        }

        if ($request->get('del-image')) {
            $file = $this->getConfig()->getDataPath().$this->ex->getImage();
            if ($this->ex->getImage()) {
                if (is_file($file)) unlink($file);
                $this->ex->setImage('');
                $this->ex->save();
                Alert::addSuccess('Image successfully deleted');
            }
            Uri::create()->remove('del-image')->redirect();
        }

//        // Enable HTMX
//        if ($request->headers->has('HX-Request')) {
//            $this->getForm()->setAttr('hx-post', Uri::create('/form/user/' . $id));
//            $this->getForm()->setAttr('hx-target', 'this');
//            $this->getForm()->setAttr('hx-swap', 'outerHTML');
//        }

        $group = 'left';
        $this->getForm()->appendField(new Field\Hidden('id'))->setGroup($group);
        $this->getForm()->appendField(new Field\Input('name'))->setGroup($group)->setRequired();
        /** @var Form\Field\File $image */
        $image = $this->getForm()->appendField(new Form\Field\File('image'))->setGroup($group);
        if ($this->ex->getImage()) {
            $image->setViewUrl($this->getConfig()->getDataUrl() . $this->ex->getImage());
            $image->setDeleteUrl(Uri::create()->set('del-image', $this->ex->getId()));
        }
//        $fileList = $this->getForm()->appendField(new \Bs\Form\Field\File('fileList', $this->ex))->setGroup($group);

        $this->getForm()->appendField(new Field\Checkbox('active', ['Enable Example' => 'active']))->setGroup($group);
//        $this->getForm()->appendField(new Field\Textarea('content'))->setGroup($group);
        $this->getForm()->appendField(new Field\Textarea('notes'))->setGroup($group);

        $this->getForm()->appendField(new Action\SubmitExit('save', [$this, 'onSubmit']));
        $this->getForm()->appendField(new Action\Link('cancel', Uri::create('/exampleManager')));

        $load = $this->ex->getMapper()->getFormMap()->getArray($this->ex);
        $load['id'] = $this->ex->getId();
        $this->getForm()->setFieldValues($load); // Use form data mapper if loading objects

        $this->getForm()->execute($request->request->all());

        $this->setFormRenderer(new FormRenderer($this->getForm()));

//        if ($request->headers->has('HX-Request')) {
//            return $this->show();
//        }
    }

    public function onSubmit(Form $form, Action\ActionInterface $action)
    {
        $this->ex->getMapper()->getFormMap()->loadObject($this->ex, $form->getFieldValues());

        // TODO: validate file uploads

        $form->addFieldErrors($this->ex->validate());
        if ($form->hasErrors()) {
            return;
        }

        /** @var Form\Field\File $fileOne */
        $image = $form->getField('image');
        if ($image->hasFile()) {
            $filepath = $image->move($this->getConfig()->getDataPath() . $this->ex->getDataPath());
            $filepath = str_replace($this->getConfig()->getDataPath(), '', $filepath);
            $this->ex->setImage($filepath);
        }

        $this->ex->save();

        Alert::addSuccess('Form save successfully.');
        //$action->setRedirect(Uri::create('/exampleEdit/'.$this->ex->getId()));
        $action->setRedirect(Uri::create());
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect(Uri::create('/exampleManager'));
        }

//        if (!$form->getRequest()->headers->has('HX-Request')) {
//            $action->setRedirect(Uri::create('/exampleEdit/'.$this->ex->getId()));
//        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        //$this->getForm()->getField('type')->addFieldCss('col-6');
        //$this->getForm()->getField('name')->addFieldCss('col-6');
        //$this->getForm()->getField('username')->addFieldCss('col-6');
        //$this->getForm()->getField('email')->addFieldCss('col-6');

        $renderer = $this->getFormRenderer();
//        $js = <<<JS
//            jQuery(function ($) {
//                $('[name=image]');
//            });
//        JS;
//        $renderer->getTemplate()->appendJs($js);

        return $renderer->show();
    }

}