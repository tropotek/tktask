<?php
namespace App\Controller\User;

use App\Db\User;
use App\Db\UserMap;
use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Exception;
use Tk\Form;
use Tk\Form\Field\Input;
use Tk\Form\Field\Checkbox;
use Tk\Uri;


/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Edit extends PageController
{
    protected Form $form;


    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('User Edit');
    }

    public function doDefault(Request $request, $id)
    {
        $user = UserMap::create()->find($id);
        if (!$user) {
            throw new Exception('Invalid User ID: ' . $id);
        }

        $this->form = Form::create('test');

        $this->form->appendField(new Input('nameFirst'));
        $this->form->appendField(new Input('nameLast'))->setRequired();
        $this->form->appendField(new Input('username'));
        $this->form->appendField(new Input('password'))->setType('password');
        $this->form->appendField(new Input('email'));
        $this->form->appendField(new Checkbox('active', ['Enable User Login' => 'active']));

        $this->form->appendField(new Form\Action\Link('cancel', Uri::create('/home')));
        $this->form->appendField(new Form\Action\Submit('save', function (Form $form, Form\Action\ActionInterface $action) use ($id) {
            /** @var User $user */
            $user = UserMap::create()->find($id);
            $user->getMapper()->getFormMap()->loadObject( $user, $this->form->getFieldValues());

            $form->setErrors($user->validate());
            if ($form->hasErrors()) {
                return;
            }

            $user->save();

            $action->setRedirect(Uri::create());
        }));

        $load = [];
        $user->getMapper()->getFormMap()->loadArray($load, $user);
        $this->form->setFieldValues($load); // Use form data mapper if loading objects

        $this->form->execute($request->request->all());

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        // Setup field group widths with bootstrap classes
        $this->form->getField('nameFirst')->setGroupAttr('class', 'col-6');
        $this->form->getField('nameLast')->setGroupAttr('class', 'col-6');
        $this->form->getField('username')->setGroupAttr('class', 'col-6');
        $this->form->getField('password')->setGroupAttr('class', 'col-6');

        $formRenderer = new Form\Renderer($this->form, $this->makePath($this->getConfig()->get('form.template.path')));
        $template->appendTemplate('content', $formRenderer->show());


        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
  <h2 var="title"></h2>
  <div var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }


}