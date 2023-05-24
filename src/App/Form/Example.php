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

//        // Enable HTMX
//        if ($request->headers->has('HX-Request')) {
//            $this->getForm()->setAttr('hx-post', Uri::create('/form/user/' . $id));
//            $this->getForm()->setAttr('hx-target', 'this');
//            $this->getForm()->setAttr('hx-swap', 'outerHTML');
//        }

        $group = 'left';
        $this->getForm()->appendField(new Field\Hidden('id'))->setGroup($group);
        $this->getForm()->appendField(new Field\Input('name'))->setGroup($group)->setRequired();

        $image = $this->getForm()->appendField(new Form\Field\File('image'))->setGroup($group);
        $fileList = $this->getForm()->appendField(new \Bs\Form\Field\File('fileList', $this->ex))->setGroup($group);

        $this->getForm()->appendField(new Field\Checkbox('active', ['Enable Example' => 'active']))->setGroup($group);
//        $this->getForm()->appendField(new Field\Textarea('content'))->setGroup($group);
        $this->getForm()->appendField(new Field\Textarea('notes'))->setGroup($group);

        $this->getForm()->appendField(new Action\Link('back', Uri::create('/exampleManager')));
        $this->getForm()->appendField(new Action\Submit('save', [$this, 'onSubmit']));

        $load = $this->ex->getMapper()->getFormMap()->getArray($this->ex);
        $load['id'] = $this->ex->getId();
        //$load['image'] = '/data/path/to/test.jpg';
        $this->getForm()->setFieldValues($load); // Use form data mapper if loading objects

        $this->getForm()->execute($request->request->all());

//        if ($request->headers->has('HX-Request')) {
//            return $this->show();
//        }
    }

    public function onSubmit(Form $form, Action\ActionInterface $action)
    {
        $this->ex->getMapper()->getFormMap()->loadObject($this->ex, $form->getFieldValues());

        $form->addFieldErrors($this->ex->validate());
        if ($form->hasErrors()) {
            return;
        }

        $this->ex->save();

        /** @var Form\Field\File $fileOne */
        $image = $form->getField('image');
        if ($image->hasFile()) {
            //vd($form->getField('image')->getValue());

        }

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create('/exampleEdit/'.$this->ex->getId()));

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

        $renderer = new FormRenderer($this->getForm());
//        $js = <<<JS
//            jQuery(function ($) {
//
//            });
//        JS;
//        $renderer->getTemplate()->appendJs($js);

        return $renderer->show();
    }

}