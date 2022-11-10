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


/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class User
{
    use SystemTrait;

    protected Form $form;

    protected FormRenderer $renderer;

    public function __construct()
    {
        $this->form = Form::create('user-edit');
    }

    public function doDefault(Request $request, $id)
    {
        $user = UserMap::create()->find($id);
        if (!$user) {
            throw new Exception('Invalid User ID: ' . $id);
        }

        if ($request->headers->has('HX-Request')) {
            // Enable HTMX
            $this->form->getForm()->setAttr('hx-post', Uri::create('/form/user/' . $id));
            $this->form->getForm()->setAttr('hx-target', 'this');
            $this->form->getForm()->setAttr('hx-swap', 'outerHTML');
        }

        $this->form->appendField(new Hidden('id'));
        $this->form->appendField(new Input('nameFirst'));
        $this->form->appendField(new Input('nameLast'))->setRequired();
        $this->form->appendField(new Input('username'));
        $this->form->appendField(new Input('password'))->setType('password');
        $this->form->appendField(new Input('email'));
        $this->form->appendField(new Checkbox('active', ['Enable User Login' => 'active']));
        $this->form->appendField(new Form\Field\Textarea('notes'))->setAttr('rows', '5');

        $this->form->appendField(new Form\Action\Link('back', Uri::create('/userManager')));
        $this->form->appendField(new Form\Action\Submit('save', [$this, 'doSubmit']));
        //$this->form->appendField(new Form\Action\Submit('save', 'App\Controller\User\FormView::doSubmit'));

        $load = [];
        $user->getMapper()->getFormMap()->loadArray($load, $user);
        $load['id'] = $user->getId();
        $this->form->setFieldValues($load); // Use form data mapper if loading objects

        $this->form->execute($request->request->all());

        return $this->show();
    }

    public function doSubmit(Form $form, Form\Action\ActionInterface $action)
    {
        /** @var \App\Db\User $user */
        $user = UserMap::create()->find($form->getFieldValue('id'));
        $user->getMapper()->getFormMap()->loadObject( $user, $form->getFieldValues());

        $form->setErrors($user->validate());
        if ($form->hasErrors()) {
            $form->getSession()->getFlashBag()->add('danger', 'Form contains errors.');
            return;
        }

        $user->save();

        $form->getSession()->getFlashBag()->add('success', 'Form save successfully.');

        if (!$form->getRequest()->headers->has('HX-Request')) {
            $action->setRedirect(Uri::create('/userManager'));
        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        $this->form->getField('nameFirst')->setGroupAttr('class', 'col-6');
        $this->form->getField('nameLast')->setGroupAttr('class', 'col-6');
        $this->form->getField('username')->setGroupAttr('class', 'col-6');
        $this->form->getField('password')->setGroupAttr('class', 'col-6');

        $this->renderer = new FormRenderer($this->form, $this->makePath($this->getConfig()->get('template.path.form')));

        return $this->renderer->show();
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function getRenderer(): FormRenderer
    {
        return $this->renderer;
    }

}