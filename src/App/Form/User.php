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

    protected \App\Db\User $user;

    protected Form $form;

    protected FormRenderer $renderer;

    public function __construct()
    {
        $this->form = Form::create('user-edit');
    }

    public function doDefault(Request $request, $id)
    {
        $this->user = new \App\Db\User();

        if ($id) {
            $this->user = UserMap::create()->find($id);
        }
        if (!$this->user) {
            throw new Exception('Invalid User ID: ' . $id);
        }

        if ($request->headers->has('HX-Request')) {
            // Enable HTMX
            $this->form->getForm()->setAttr('hx-post', Uri::create('/form/user/' . $id));
            $this->form->getForm()->setAttr('hx-target', 'this');
            $this->form->getForm()->setAttr('hx-swap', 'outerHTML');
        }

        $group = 'left';
        $this->form->appendField(new Hidden('id'))->setGroup($group);
        $list = array('-- Type --' => '', 'Admin' => 'admin', 'Member' => 'member');
        $this->form->appendField(new Form\Field\Select('type', $list))->setGroup($group);
        $this->form->appendField(new Input('nameFirst'))->setGroup($group)->setRequired();
        $this->form->appendField(new Input('nameLast'))->setGroup($group);
        $this->form->appendField(new Input('username'))->setGroup($group)->setRequired();
        $this->form->appendField(new Input('password'))
            ->setGroup($group)->setRequired()->addCss('tk-input-lock')->setType('password');


        $this->form->appendField(new Input('email'))->setGroup($group)->setRequired();
        $this->form->appendField(new Checkbox('active', ['Enable User Login' => 'active']))->setGroup($group);
        $this->form->appendField(new Form\Field\Textarea('notes'))
            ->addCss('mce')->setAttr('rows', '5')->setGroup($group);

        $this->form->appendField(new Form\Action\Link('back', Uri::create('/userManager')));
        $this->form->appendField(new Form\Action\Submit('save', [$this, 'doSubmit']));
        //$this->form->appendField(new Form\Action\Submit('save', 'App\Controller\User\FormView::doSubmit'));

        $load = [];
        $this->user->getMapper()->getFormMap()->loadArray($load, $this->user);
        $load['id'] = $this->user->getId();
        $this->form->setFieldValues($load); // Use form data mapper if loading objects

        $this->form->execute($request->request->all());

        return $this->show();
    }

    public function doSubmit(Form $form, Form\Action\ActionInterface $action)
    {
        $this->user->getMapper()->getFormMap()->loadObject($this->user, $form->getFieldValues());

        $form->setErrors($this->user->validate());
        if ($form->hasErrors()) {
            $form->getSession()->getFlashBag()->add('danger', 'Form contains errors.');
            return;
        }

        $this->user->save();

        $form->getSession()->getFlashBag()->add('success', 'Form save successfully.');

        if (!$form->getRequest()->headers->has('HX-Request')) {
            $action->setRedirect(Uri::create());
            //$action->setRedirect(Uri::create('/userManager'));
        }
    }

    public function show(): ?Template
    {
        // Setup field group widths with bootstrap classes
        $this->form->getField('nameFirst')->setFieldAttr('class', 'col-6');
        $this->form->getField('nameLast')->setFieldAttr('class', 'col-6');
        $this->form->getField('username')->setFieldAttr('class', 'col-6');
        $this->form->getField('password')->setFieldAttr('class', 'col-6');
        //$this->form->getField('password')->setAttr('readonly')->setAttr('onfocus', "this.removeAttribute('readonly');this.removeAttribute('placeholder');");
        //$this->form->getField('password')->setReadonly(true)->setAttr('onfocus', 'this.removeAttribute(\'readonly\');');

        $this->renderer = new FormRenderer($this->form, $this->makePath($this->getConfig()->get('template.path.form')));

        return $this->renderer->show();
    }

    public function getUser(): \App\Db\User
    {
        return $this->user;
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