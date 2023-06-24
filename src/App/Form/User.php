<?php
namespace App\Form;

use App\Db\UserMap;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
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

    protected string $type = \App\Db\User::TYPE_USER;


    public function __construct()
    {
        $this->setForm(Form::create('user'));
    }

    public function doDefault(Request $request, int $id, string $type = \App\Db\User::TYPE_USER)
    {
        $this->type = $type;
        $this->user = new \App\Db\User();
        $this->getUser()->setType($type);

        if ($id > 0) {
            $this->user = UserMap::create()->find($id);
            if (!$this->getUser()) {
                throw new Exception('Invalid User ID: ' . $id);
            }
        }

        $group = 'Details';
        $this->getForm()->appendField(new Hidden('id'))->setGroup($group);
        $this->getForm()->appendField(new Input('name'))->setGroup($group)
            ->setRequired();

        $l1 = $this->getForm()->appendField(new Input('username'))->setGroup($group)
            ->setRequired();

        $l2 = $this->getForm()->appendField(new Input('email'))->setGroup($group)
            ->setRequired();

        // Only input lock existing user
        if ($this->getUser()->getId()) {
            $l1->addCss('tk-input-lock');
            $l2->addCss('tk-input-lock');
        }

        if ($this->getUser()->isStaff() && $this->getFactory()->getAuthUser()->hasPermission(\App\Db\User::PERM_SYSADMIN)) {
            $field = $this->getForm()->appendField(new Checkbox('perm', array_flip(\App\Db\User::PERMISSION_LIST)))
                ->setLabel('Permissions')
                ->setGroup($group);

            if ($this->getUser()->getUsername() == 'admin') {
                $field->setDisabled();
            }

            $this->getForm()->appendField(new Checkbox('active', ['Enable User Login' => 'active']))
                ->setGroup($group);
        }

        $this->getForm()->appendField(new Form\Field\Textarea('notes'))
            ->setGroup($group);


        $this->getForm()->appendField(new Form\Action\SubmitExit('save', [$this, 'onSubmit']));
        $this->getForm()->appendField(new Form\Action\Link('back', $this->getFactory()->getBackUrl()));

        $load = $this->getUser()->getMapper()->getFormMap()->getArray($this->getUser());
        $load['id'] = $this->getUser()->getId();
        $load['perm'] = $this->getUser()->getPermissionList();
        $this->getForm()->setFieldValues($load); // Use form data mapper if loading objects

        $this->getForm()->execute($request->request->all());

        $this->setFormRenderer(new FormRenderer($this->getForm()));

    }

    public function onSubmit(Form $form, Form\Action\ActionInterface $action)
    {
        if ($this->getUser()->getUsername() == 'admin') {
            $form->removeField('perm');
        }

        $this->getUser()->getMapper()->getFormMap()->loadObject($this->user, $form->getFieldValues());
        if ($form->getField('perm')) {
            $this->getUser()->setPermissions(array_sum($form->getFieldValue('perm') ?? []));
        }

        $form->addFieldErrors($this->user->validate());
        if ($form->hasErrors()) {
            Alert::addError('Form contains errors.');
            return;
        }

        $isNew = $this->getUser()->getId() == 0;
        $this->getUser()->save();

        // Send email to update password
        if ($isNew) {
            $this->getUser()->sendRecoverEmail(true);
            Alert::addSuccess('An email has been sent to ' . $this->getUser()->getEmail() . ' to create their password.');
        }

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create('/user/'.$this->type.'Edit')->set('id', $this->getUser()->getId()));
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect($this->getFactory()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        //$this->getForm()->getField('type')->addFieldCss('col-6');
        //$this->getForm()->getField('name')->addFieldCss('col-6');
        $this->getForm()->getField('username')->addFieldCss('col-6');
        $this->getForm()->getField('email')->addFieldCss('col-6');

        $renderer = $this->getFormRenderer();
        $renderer->addFieldCss('mb-3');

        return $renderer->show();
    }

    public function getUser(): \App\Db\User
    {
        return $this->user;
    }
}