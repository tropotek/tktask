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
        $this->form->appendField(new Form\Field\Hidden('hidden'))->setLabel('Hide Me!');
        $this->form->appendField(new Form\Field\Input('email'))->setType('email')->setAttr('data-foo', 'Foo is good!!');
        $this->form->appendField(new Form\Field\Input('password'));
        $this->form->appendField(new Form\Field\Input('address'));
        $list = ['-- Select --' => '', 'VIC' => 'Victoria', 'NSW' => 'New South Wales', 'WA' => 'Western Australia'];
        $this->form->appendField(new Form\Field\Select('state', $list));

        $submit = $this->form->appendField(new Form\Action\Submit('save', function (Form $form, Form\Action\ActionInterface $action) {
            vd($this->form->getFieldValues());
            $action->setRedirect(Uri::create());
        }));




        $load = [
            'email' => 'test@example.com',
            'password' => 'shh-secret',
            'address' => 'homeless',
            'hidden' => 123
        ];
        $this->form->setFieldValues($load); // Use form data mapper if loading objects\
        $this->form->execute($request->request->all());


        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        $formRenderer = new Form\Renderer($this->form);


        $template->appendTemplate('content', $formRenderer->show());

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


