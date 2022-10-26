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


        $t = $this->loadTemplateFile($this->getSystem()->makePath('/html/templates/Tk/Form/DomRenderer.xtpl'));
        $template->prependTemplate('content', $t);


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


