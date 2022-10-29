<?php
namespace App\Controller\Ui;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Form;
use Tk\Uri;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class FormEg extends PageController
{

    protected Form $form;

    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('Form');
    }

    public function doDefault(Request $request)
    {
        Template::$ENABLE_TRACER = true;

        $this->form = Form::create('test');
        $this->form->appendField(new Form\Field\Input('email'))->setType('email')->setAttr('data-foo', 'Foo is good!!');
        $this->form->appendField(new Form\Field\Input('password'));
        $this->form->appendField(new Form\Field\Input('address'));
        $list = ['-- Select --' => '', 'VIC' => 'vic', 'NSW' => 'nsw'];
        $this->form->appendField(new Form\Field\Select('state', $list));

        $load = [
            'email' => 'test@example.com',
            'password' => 'shh-secret',
            'address' => 'homeless'
        ];
        $this->form->loadValues($load);

        $submit = $this->form->appendField(new Form\Action\Submit('save', function (Form $form, Form\Action\ActionInterface $action) {
            vd($this->form->getValues());
            $action->setRedirect(Uri::create());
        }));

        $this->form->execute($request->request->all());


        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());


        $error = false;
        $fr = new Form\Renderer($this->form);

        $fmt = $fr->buildTemplate('form');

        $ft = $fr->buildTemplate('input');
        $ft->setText('label', 'Field Test');
        $ft->setText('notes', 'This is some test help text...');
        $ft->setAttr('element', 'name', 'test');

        $ft1 = $fr->buildTemplate('input');
        $ft1->setText('label', 'Email Test');
        $ft1->setText('notes', 'This is some test help text...');
        $ft1->setAttr('element', 'type', 'email');
        $ft1->setAttr('element', 'required');
        $ft1->setAttr('element', 'name', 'email');
        $ft1->setText('error', 'Invalid email value');
        if ($error){
            $ft->addCss('element', 'is-invalid');
        }

        $ft2 = $fr->buildTemplate('switch');
        //$ft2->setText('label', 'Switch');
        for($i = 0; $i < 5; $i++) {
            $r = $ft2->getRepeat('option');
            $id = 'switch' . $i;
            $r->setText('label', 'Switch No ' . $i);
            $r->setAttr('label', 'for', $id);
            $r->setAttr('element', 'id', $id);
            $r->setAttr('option', 'data-var', $id);
            $r->setAttr('element', 'value', $id);
            $r->setAttr('element', 'name', 'switch[]');
            $r->addCss('option', 'me-5');
            if ($error) {
                $r->addCss('element', 'is-invalid');
            }
            $r->appendRepeat();
        }
        if ($error) {
            $ft2->setText('error', 'Invalid switch combination');
        }
        $ft2->setText('notes', 'This is some test help text...');

        $ft3 = $fr->buildTemplate('checkbox');
        //$ft2->setText('label', 'Switch');
        for($i = 0; $i < 5; $i++) {
            $r = $ft3->getRepeat('option');
            $id = 'cb-' . $i;
            $r->setText('label', 'Checkbox No ' . $i);
            $r->setAttr('label', 'for', $id);
            $r->setAttr('element', 'id', $id);
            $r->setAttr('option', 'data-var', $id);
            $r->setAttr('element', 'value', $id);
            $r->setAttr('element', 'name', 'checkbox[]');
            $r->addCss('option', 'me-5');
            if ($error) {
                $r->addCss('element', 'is-invalid');
            }
            $r->appendRepeat();
        }
        if ($error) {
            $ft3->setText('error', 'Invalid checkbox combination');
        }
        $ft3->setText('notes', 'This is some test help text...');



        if ($this->getRequest()->request->has('send')) {
            vd($this->getRequest()->request->all());
            $email = $this->getRequest()->request->get('email', '');
            $ft1->setAttr('element', 'value', $email);
            if (str_ends_with($email, '.com')) {
                $ft1->addCss('element', 'is-invalid');
            }

        }


        $fmt->appendTemplate('fields', $ft);
        $fmt->appendTemplate('fields', $ft1);
        $fmt->appendTemplate('fields', $ft2);
        $fmt->appendTemplate('fields', $ft3);


        $ftb = $fr->buildTemplate('submit');
        $ftb->setattr('element', 'name', 'send');
        $ftb->setText('element', 'Send');
        $fmt->appendTemplate('actions', $ftb);


        $template->appendTemplate('content', $fmt);

        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
  <h3 var="title"></h3>
  <div var="content">

  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


