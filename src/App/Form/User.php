<?php
namespace App\Form;

use App\Db\UserMap;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Exception;
use Tk\Form;
use Tk\FormRenderer;
use Tk\Form\Field\Input;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\Hidden;
use Tk\Traits\SystemTrait;
use Tk\Uri;

class User
{
    use SystemTrait;
    use Form\FormTrait;

    protected ?\App\Db\User $user = null;


    public function __construct()
    {
        $this->setForm(Form::create('user-edit'));
    }

    public function doDefault(Request $request, $id)
    {
        $this->user = new \App\Db\User();
        $this->getUser()->setType(\App\Db\User::TYPE_USER);
        if ($request->query->get('type') == \App\Db\User::TYPE_STAFF) {
            $this->getUser()->setType(\App\Db\User::TYPE_STAFF);
        }

        if ($id) {
            $this->user = UserMap::create()->find($id);
            if (!$this->getUser()) {
                throw new Exception('Invalid User ID: ' . $id);
            }
        }

        // Enable HTMX
        if ($request->headers->has('HX-Request')) {
            $this->getForm()->setAttr('hx-post', Uri::create('/form/user/' . $id));
            $this->getForm()->setAttr('hx-target', 'this');
            $this->getForm()->setAttr('hx-swap', 'outerHTML');
        }

        $group = 'left';
        $this->getForm()->appendField(new Hidden('id'))->setGroup($group);
        $this->getForm()->appendField(new Input('name'))->setGroup($group)->setRequired();

//        $list = ['Staff' => 'staff', 'User' => 'user'];
//        $this->form->appendField(new Form\Field\Select('type', $list))->prependOption('-- Type --', '')->setGroup($group);

        $this->getForm()->appendField(new Input('username'))->addCss('tk-input-lock')->setGroup($group)->setRequired();
        $this->getForm()->appendField(new Input('email'))->addCss('tk-input-lock')->setGroup($group)->setRequired();

        if ($this->user->isType(\App\Db\User::TYPE_STAFF)) {
            $this->getForm()->appendField(new Checkbox('perm', array_flip(\App\Db\User::PERMISSION_LIST)))->setGroup($group);
        }

        $this->getForm()->appendField(new Checkbox('active', ['Enable User Login' => 'active']))->setGroup($group);
        $this->getForm()->appendField(new Form\Field\Textarea('notes'))->setGroup($group);

        $this->getForm()->appendField(new Form\Action\Link('back', Uri::create('/'.$this->getUser()->getType().'Manager')));
        $this->getForm()->appendField(new Form\Action\Submit('save', [$this, 'onSubmit']));

        $load = $this->getUser()->getMapper()->getFormMap()->getArray($this->getUser());
        $load['id'] = $this->getUser()->getId();
        $load['perm'] = $this->getUser()->getPermissionList();
        $this->getForm()->setFieldValues($load); // Use form data mapper if loading objects

        $this->getForm()->execute($request->request->all());

        if ($request->headers->has('HX-Request')) {
            return $this->show();
        }
    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action)
    {
        $this->getUser()->getMapper()->getFormMap()->loadObject($this->user, $form->getFieldValues());
        $this->getUser()->setPermissions(array_sum($form->getFieldValue('perm') ?? []));

        $form->setErrors($this->user->validate());
        if ($form->hasErrors()) {
            $form->getSession()->getFlashBag()->add('danger', 'Form contains errors.');
            return;
        }

        $this->getUser()->save();

        $form->getSession()->getFlashBag()->add('success', 'Form save successfully.');

        if (!$form->getRequest()->headers->has('HX-Request')) {
            $action->setRedirect(Uri::create());
        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        //$this->getForm()->getField('type')->addFieldCss('col-6');
        //$this->getForm()->getField('name')->addFieldCss('col-6');
        $this->getForm()->getField('username')->addFieldCss('col-6');
        $this->getForm()->getField('email')->addFieldCss('col-6');

        $renderer = new FormRenderer($this->getForm());

//        $js = <<<JS
//            jQuery(function ($) {
//
//            });
//        JS;
//        $renderer->getTemplate()->appendJs($js);

        return $renderer->show();
    }

    public function getUser(): \App\Db\User
    {
        return $this->user;
    }
}