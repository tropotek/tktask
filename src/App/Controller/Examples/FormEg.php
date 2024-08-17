<?php
namespace App\Controller\Examples;

use Bs\ControllerDomInterface;
use Bs\Form;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Form\Action\Link;
use Tk\Form\Action\Submit;
use Tk\Form\Action\SubmitExit;
use Tk\Form\Field\Checkbox;
use Tk\Form\Field\File;
use Tk\Form\Field\Hidden;
use Tk\Form\Field\Input;
use Tk\Form\Field\InputButton;
use Tk\Form\Field\Radio;
use Tk\Form\Field\Select;
use Tk\Form\Field\Textarea;
use Tk\Uri;

class FormEg extends ControllerDomInterface
{

    protected Form $form;


    public function doDefault(Request $request): void
    {
        $this->getPage()->setTitle('Form');

        $this->form = new Form;
        $this->form->appendField(new Hidden('action'))->setValue('testAction')->setLabel('Hide Me!');

        $this->form->appendField(new Input('email'))->setType('email');
        $this->form->appendField(new Input('test'));
        $this->form->appendField(new Input('address'))->setNotes('Only upload valid addresses');
        $list = ['-- Select --' => '', 'VIC' => 'Victoria', 'NSW' => 'New South Wales', 'WA' => 'Western Australia'];
        $this->form->appendField(new Select('state', $list))
            ->setNotes('This is a select box');

        $this->form->appendField(new Input('date1'))
            //->setRequired()
            ->addCss('date')->setAttr('data-max-date', '+1w');

        // Native HTML datepicker has issues with unsupported browsers and required input:
        // See: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/date
        $this->form->appendField(new Input('date2'))
            ->setRequired()
            ->setType('date')->setAttr('pattern', '\d{4}-\d{2}-\d{2}');

        $this->form->appendField(new Input('date3'));
        $this->form->appendField(new Input('date4'));

        $this->form->appendField(new InputButton('autocomplete'))
            ->addBtnCss('fa fa-chevron-down');

        $files = $this->form->appendField(new File('attach'))->setNotes('Only upload valid files'); //->setMultiple(true);

        $this->form->appendField(new Checkbox('active'));

        $this->form->appendField(new Checkbox('checkbox', [
            'Checkbox 1' => 'cb_1',
            'Checkbox 2' => 'cb_2',
            'Checkbox 3' => 'cb_3',
            'Checkbox 4' => 'cb_4'
        ]));
        $this->form->appendField(new Radio('radio', [
            'Radio 1' => 'rb_1',
            'Radio 2' => 'rb_2',
            'Radio 3' => 'rb_3',
            'Radio 4' => 'rb_4'
        ]));
        $this->form->appendField(new Checkbox('switch', [
            'Switch 1' => 'sw_1',
            'Switch 2' => 'sw_2',
            'Switch 3' => 'sw_3',
            'Switch 4' => 'sw_4'
        ]))->setSwitch(true);

        $this->form->appendField(new Textarea('notes'));

        $this->form->appendField(new Textarea('tinyMce'))->addCss('mce');


        $this->form->appendField(new Link('cancel', Uri::create('/home')));
        $this->form->appendField(new SubmitExit('save2', [$this, 'onSubmit']));
        $this->form->appendField(new Submit('save', [$this, 'onSubmit']));


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

    }

    public function onSubmit(Form $form, Submit $action): void
    {
        $form->addFieldError('test', 'this is a test error');
        //vd($form->getAllErrors());

        if ($form->hasErrors()) {
            return;
        }

        //vd($form->getFieldValues());
        // ...

        Alert::addSuccess('Form save successfully.');
        $action->setRedirect(Uri::create());
        if ($form->getTriggeredAction()->isExit()) {
            $action->setRedirect($this->getFactory()->getBackUrl());
        }
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        // Setup field group widths with bootstrap classes
        $this->form->getField('email')->addFieldCss('col-6');
        $this->form->getField('test')->addFieldCss('col-6');
        $this->form->getField('address')->addFieldCss('col-6');
        $this->form->getField('state')->addFieldCss('col-6');

        $this->form->getField('date1')->addFieldCss('col-3');
        $this->form->getField('date2')->addFieldCss('col-3');
        $this->form->getField('date3')->addFieldCss('col-3');
        $this->form->getField('date4')->addFieldCss('col-3');

        $template->appendHtml('content', $this->form->show());

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
    // $('[name=category]').autocomplete({
    //   source: availableTags,
    //   minLength: 0  // Must be 0 for dropdown btn to work
    // });

    // Show the dropdown on click
    // $('.fld-autocomplete button').on('click', function () {
    //     $('[name=autocomplete]').autocomplete('search', $('[name=autocomplete]').val());
    // });

});
JS;
        $template->appendJs($js);


        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-cogs"></i> Actions</div>
    <div class="card-body" var="actions">
      <a href="/" title="Back" class="btn btn-outline-secondary" var="back"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header" var="title"><i class="fa fa-cogs"></i> </div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


