<?php
namespace App\Controller\Ui;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Form;
use Tk\FormRenderer;
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
        $this->form->appendField(new Form\Field\Input('address'))->setNotes('Only upload valid addresses');
        $list = ['-- Select --' => '', 'VIC' => 'Victoria', 'NSW' => 'New South Wales', 'WA' => 'Western Australia'];
        $this->form->appendField(new Form\Field\Select('state', $list))
            ->setNotes('This is a select box');
        $files = $this->form->appendField(new Form\Field\File('attach'))->setNotes('Only upload valid files'); //->setMultiple(true);

        $this->form->appendField(new Form\Field\Checkbox('active'));
        $this->form->appendField(new Form\Field\Checkbox('checkbox', [
            'Checkbox 1' => 'cb_1',
            'Checkbox 2' => 'cb_2',
            'Checkbox 3' => 'cb_3',
            'Checkbox 4' => 'cb_4'
        ]));
//        $this->form->appendField(new Form\Field\Radio('radio', [
//            'Radio 1' => 'rb_1',
//            'Radio 2' => 'rb_2',
//            'Radio 3' => 'rb_3',
//            'Radio 4' => 'rb_4'
//        ]));
//        $this->form->appendField(new Form\Field\Checkbox('switch', [
//            'Switch 1' => 'sw_1',
//            'Switch 2' => 'sw_2',
//            'Switch 3' => 'sw_3',
//            'Switch 4' => 'sw_4'
//        ]))->setType('switch');

        $this->form->appendField(new Form\Field\Textarea('notes'));

        $this->form->appendField(new Form\Field\Textarea('tinyMceMin'))->addCss('mce-min');

        $this->form->appendField(new Form\Field\Textarea('tinyMce'))->addCss('mce');


        $this->form->appendField(new Form\Action\Submit('save', function (Form $form, Form\Action\ActionInterface $action) use ($files) {
            vd($this->form->getFieldValues());
            vd($files->getValue());
            $action->setRedirect(Uri::create());
        }));
        $this->form->appendField(new Form\Action\Link('cancel', Uri::create('/home')));


        $load = [
            'email' => 'test@example.com',
            'password' => 'shh-secret',
            'address' => 'homeless',
            'hidden' => 123,
            'radio' => 'rb_2',
            'active' => 'active',
            'checkbox' => [ 'cb_1', 'cb_4' ],
            'switch' => [ 'sw_2', 'sw_3' ],
        ];
        $this->form->setFieldValues($load); // Use form data mapper if loading objects

        $this->form->execute($request->request->all());

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        // Setup field group widths with bootstrap classes
        $this->form->getField('email')->setFieldAttr('class', 'col-6');
        $this->form->getField('password')->setFieldAttr('class', 'col-6');
        $this->form->getField('address')->setFieldAttr('class', 'col-8');
        $this->form->getField('state')->setFieldAttr('class', 'col-4');

        $formRenderer = new FormRenderer($this->form, $this->makePath($this->getConfig()->get('template.path.form')));
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


