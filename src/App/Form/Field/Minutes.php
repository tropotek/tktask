<?php
namespace App\Form\Field;

use Dom\Renderer\DisplayInterface;
use Dom\Renderer\RendererInterface;
use Dom\Renderer\Traits\RendererTrait;
use Dom\Template;
use Tk\Form\Field\FieldInterface;
use Tk\Form\Renderer\Dom\Field\Input;

/**
 * This field is a select with a checkbox.
 * The checkbox state is not saved, and is reset to the default value
 * on each page load. it is meant to be used as a trigger element.
 *
 * Be sure to add the \App\Dorm\DataMap\Minutes object to the Form mapper for this field
 */
class Minutes extends FieldInterface implements DisplayInterface, RendererInterface
{
    use RendererTrait;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->setMultiple(true);
        $this->setAttr('maxlength', '2');
    }

    public function show(): ?Template
    {
        $t = $this->getTemplate();

        // Render standard select box
        $renderer = new Input($this);
        $renderer->setTemplate($t);
        $renderer->show();

        $t->setAttr('hrs', $this->getAttrList());
        $t->addCss('hrs', $this->getCssList());
        $t->setAttr('mins', $this->getAttrList());
        $t->addCss('mins', $this->getCssList());

        $t->setAttr('hrs', 'name', $this->getName().'[hours]');
        $t->setAttr('mins', 'name', $this->getName().'[minutes]');

        $values = $this->getValue();
        $t->setAttr('hrs', 'value', $values['hours'] ?? '0');
        $t->setAttr('mins', 'value', $values['minutes'] ?? '0');


        // See the app.js for script code....


        return $t;
    }

    public function __makeTemplate(): Template
    {
        $html = <<<HTML
<div var="field" class="tk-minutes">
  <label class="form-label" var="label"></label>
  <div class="input-group">
    <input type="text" class="form-control hrs" var="hrs" placeholder="Hours" data-bs-toggle="dropdown" aria-expanded="false">
    <ul class="dropdown-menu dropdown-menu-start tk-hrs-opts">
      <li><h6 class="dropdown-header">Hours</h6></li>
      <li><a class="dropdown-item" href="#">0</a></li>
      <li><a class="dropdown-item" href="#">1</a></li>
      <li><a class="dropdown-item" href="#">2</a></li>
      <li><a class="dropdown-item" href="#">3</a></li>
      <li><a class="dropdown-item" href="#">4</a></li>
      <li><a class="dropdown-item" href="#">5</a></li>
      <li><a class="dropdown-item" href="#">6</a></li>
      <li><a class="dropdown-item" href="#">7</a></li>
      <li><a class="dropdown-item" href="#">8</a></li>
      <li><a class="dropdown-item" href="#">9</a></li>
      <li><a class="dropdown-item" href="#">10</a></li>
    </ul>
    <span class="input-group-text">:</span>
    <input type="text" class="form-control mins" var="mins" placeholder="Minutes" data-bs-toggle="dropdown" aria-expanded="false">
    <ul class="dropdown-menu dropdown-menu-end tk-mins-opts">
      <li><h6 class="dropdown-header">Minutes</h6></li>
      <li><a class="dropdown-item" href="#">0</a></li>
      <li><a class="dropdown-item" href="#">15</a></li>
      <li><a class="dropdown-item" href="#">30</a></li>
      <li><a class="dropdown-item" href="#">45</a></li>
    </ul>
  </div>
  <div class="form-text text-secondary" choice="notes"></div>
  <div class="invalid-feedback" choice="error"></div>
</div>
HTML;
        return Template::load($html);
    }
}