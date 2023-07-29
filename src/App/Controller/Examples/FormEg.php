<?php
namespace App\Controller\Examples;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Form;
use Tk\FormRenderer;
use Tk\Uri;

class FormEg extends PageController
{

    protected Form $form;

    public function __construct()
    {
        parent::__construct($this->getFactory()->getAdminPage());
        $this->getPage()->setTitle('Form');
    }

    public function doDefault(Request $request)
    {

        $this->form = Form::create('test');
        $this->form->appendField(new Form\Field\Hidden('hidden'))->setLabel('Hide Me!');

        $this->form->appendField(new Form\Field\Input('email'))->setType('email');
        $this->form->appendField(new Form\Field\Input('test'));
        $this->form->appendField(new Form\Field\Input('address'))->setNotes('Only upload valid addresses');
        $list = ['-- Select --' => '', 'VIC' => 'Victoria', 'NSW' => 'New South Wales', 'WA' => 'Western Australia'];
        $this->form->appendField(new Form\Field\Select('state', $list))
            ->setNotes('This is a select box');

        $this->form->appendField(new Form\Field\Input('date1'))
            ->setRequired()
            ->addCss('date')->setAttr('data-max-date', '+1w');
        // Native HTML datepicker has issues with unsupported browsers and required input:
        // See: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/date
        $this->form->appendField(new Form\Field\Input('date2'))
            ->setRequired()
            ->setType('date')->setAttr('pattern', '\d{4}-\d{2}-\d{2}');

        $this->form->appendField(new Form\Field\Input('date3'));
        $this->form->appendField(new Form\Field\Input('date4'));

        $this->form->appendField(new Form\Field\InputButton('autocomplete'))
            ->addBtnCss('fa fa-chevron-down');

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

        //$this->form->appendField(new Form\Field\Textarea('tinyMceMin'))->addCss('mce-min');

        $this->form->appendField(new Form\Field\Textarea('tinyMce'))->addCss('mce');


        $this->form->appendField(new Form\Action\Link('cancel', Uri::create('/home')));
        $this->form->appendField(new Form\Action\Submit('save', function (Form $form, Form\Action\ActionInterface $action) use ($files) {
//            vd($this->form->getFieldValues());
//            vd($files->getValue());
            $action->setRedirect(Uri::create());
        }));


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
        $template->appendText('title', $this->getPage()->getTitle());

        // Setup field group widths with bootstrap classes
        $this->form->getField('email')->addFieldCss('col-6');
        $this->form->getField('test')->addFieldCss('col-6');
        $this->form->getField('address')->addFieldCss('col-6');
        $this->form->getField('state')->addFieldCss('col-6');

        $this->form->getField('date1')->addFieldCss('col-3');
        $this->form->getField('date2')->addFieldCss('col-3');
        $this->form->getField('date3')->addFieldCss('col-3');
        $this->form->getField('date4')->addFieldCss('col-3');

        $formRenderer = new FormRenderer($this->form);
        $template->appendTemplate('content', $formRenderer->show());

        // Autocomplete js
        $js = <<<JS
jQuery(function($) {

    var availableTags = [
      "ActionScript",
      "AppleScript",
      "Asp",
      "BASIC",
      "C",
      "C++",
      "Clojure",
      "COBOL",
      "ColdFusion",
      "Erlang",
      "Fortran",
      "Groovy",
      "Haskell",
      "Java",
      "JavaScript",
      "Lisp",
      "Perl",
      "PHP",
      "Python",
      "Ruby",
      "Scala",
      "Scheme"
    ];
    $('[name=category]').autocomplete({
      source: availableTags,
      minLength: 0  // Must be 0 for dropdown btn to work
    });

    // Show the dropdown on click
    $('.fld-autocomplete button').on('click', function () {
        $('[name=autocomplete]').autocomplete('search', $('[name=autocomplete]').val());
    });

});
JS;
        $template->appendJs($js);


        return $template;
    }

    public function __makeTemplate(): ?Template
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


