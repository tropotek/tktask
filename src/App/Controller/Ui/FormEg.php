<?php
namespace App\Controller\Ui;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Form;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class FormEg extends PageController
{

    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('Form');
    }

    public function doDefault(Request $request)
    {

        $cl = $this->getFactory()->getClassLoader()->getPrefixes();
        vd($cl);
        //$form = \Tk\Form::create('test');
        //$form = new Form('test');

//        $form->appendField(new Form\Field\Input('email'));

        //vd($form->getField('email')->getId());

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());


        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
  <h3 var="title"></h3>
  <div var="content">

    <form id="TestForm" name="TestFrom_name" method="post" class="row g-3">
      <div class="col-md-6">
        <label for="inputEmail4" class="form-label">Email</label>
        <input type="email" class="form-control" id="inputEmail4" />
      </div>
      <div class="col-md-6">
        <label for="inputPassword4" class="form-label">Password</label>
        <input type="password" class="form-control" id="inputPassword4" />
      </div>
      <div class="col-12">
        <label for="inputAddress" class="form-label">Address</label>
        <input type="text" class="form-control" id="inputAddress" placeholder="1234 Main St" />
      </div>
      <div class="col-12">
        <label for="inputAddress2" class="form-label">Address 2</label>
        <input type="text" class="form-control" id="inputAddress2" placeholder="Apartment, studio, or floor" />
      </div>
      <div class="col-6">
        <label for="inputCity" class="form-label">City</label>
        <input type="text" class="form-control" id="inputCity" />
      </div>
      <div class="col-6">
        <label for="inputState" class="form-label">State</label>
        <select id="inputState" class="form-select">
          <option selected="selected">Choose...</option>
          <option>...</option>
        </select>
      </div>
      <div class="col-md-12">
        <label for="inputZip" class="form-label">Zip</label>
        <input type="text" class="form-control" id="inputZip" />
      </div>
      <div class="col-12">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="gridCheck" />
          <label class="form-check-label" for="gridCheck">
            Check me out
          </label>
        </div>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-secondary" name="cancel">Cancel</button>
        <button type="submit" class="btn btn-primary" name="save">Submit</button>
      </div>
    </form>


  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


