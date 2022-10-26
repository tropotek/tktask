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


//        $t = $this->loadTemplateFile($this->getSystem()->makePath('/html/templates/Form.xtpl'));
//        $template->prependTemplate('content', $t);


        $doc = new \DOMDocument();
        $doc->loadHTMLFile($this->getSystem()->makePath('/html/templates/Form.xtpl'));

        //$tplVars = array_filter(array_keys($t->getVarList()), fn($r) => str_starts_with($r, 'tpl-'));
        //vd($tplVars);

        $tplNames = [
            'tpl-form',
            'tpl-hidden',
            'tpl-none',
            'tpl-input',
            'tpl-textarea',
            'tpl-select',
            'tpl-checkbox',
            'tpl-radio',
            'tpl-switch',
            'tpl-file',
            'tpl-button',
            'tpl-submit',
            'tpl-link',
        ];
        foreach ($tplNames as  $name) {
            $tpl = $doc->getElementById($name);
            $tpl->removeAttribute('id');
            //vd($name, $doc->saveHTML($tpl));
        }


        $fr = new Form\Renderer($this->form);

        $fmt = $fr->getFieldTemplate('form');

        $ft = $fr->getFieldTemplate('input');
        $ft->setText('label', 'Field Test');
        $ft->setText('notes', 'This is some test help text...');
        //$ft->addCss('col', 'col');
        $fmt->appendTemplate('fields', $ft);

        $ft = $fr->getFieldTemplate('input');
        $ft->setText('label', 'Error Test');
        $ft->setText('notes', 'This is some test help text...');
        $ft->setVisible('error');
        $ft->setText('error', 'Invalid for field value.');
        $ft->addCss('element', 'is-invalid');
        //$ft->addCss('col', 'col');
        $fmt->appendTemplate('fields', $ft);

        $ft = $fr->getFieldTemplate('switch');
        vd(array_keys($ft->getVarList()));
        //$ft->setText('label', 'Switch');
        $error = 'This is a test error';
        for($i = 0; $i < 5; $i++) {
            $r = $ft->getRepeat('option');
            $id = 'switch' . $i;
            $r->setText('label', 'Switch No ' . $i);
            $r->setAttr('label', 'for', $id);
            $r->setAttr('element', 'id', $id);
            $r->setAttr('option', 'data-var', $id);
            //$r->addCss('option', 'form-check-inline');
            $r->addCss('option', 'me-5');
            if ($error) {
                $r->addCss('element', 'is-invalid');
            }
            $r->appendRepeat();
        }
        if ($error) {
            $ft->setVisible('error', true);
            $ft->setText('error', $error);
        }
        //$ft->addCss('col', 'col');
        $ft->setText('notes', 'This is some test help text...');
        $fmt->appendTemplate('fields', $ft);

        $template->appendTemplate('content', $fmt);




//        // Form renderer
//        $fr = new \Tk\Form\BsRenderer($this->form);
//
//        // Some rendering operations
//        $fr->setColCss('email', 'col-md-6');
//        $fr->setColCss('password', 'col-md-6');
//        $fr->setColCss('address', 'col-md-12');
//        // The event column is a special case and should hold all Action elements
//        $fr->setColCss(\Tk\Form\BsRenderer::EventCol, 'col-md-12');
//        $template->prependTemplate('content', $fr->show());

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


