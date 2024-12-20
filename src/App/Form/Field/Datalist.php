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
class Datalist extends FieldInterface implements DisplayInterface, RendererInterface
{
    use RendererTrait;

    protected array $list;

    public function __construct(string $name, array $list)
    {
        parent::__construct($name);
        $this->list = $list;
    }

    public function show(): ?Template
    {
        $t = $this->getTemplate();

        // Render standard select box
        $renderer = new Input($this);
        $renderer->setTemplate($t);
        $renderer->show();

        $t->setAttr('element', 'list', $this->makeRequestKey('list'));
        $t->setAttr('datalist', 'id', $this->makeRequestKey('list'));

        foreach ($this->list as $id => $option) {
            $opt = $t->getRepeat('option');
            $opt->setAttr('option', 'value', $option);
            $opt->setAttr('option', 'data-value', $id);
            $opt->appendRepeat();
        }

        return $t;
    }

    public function __makeTemplate(): Template
    {
        $html = <<<HTML
<div var="field" class="tk-datalist">
  <label class="form-label" var="label"></label>
  <input type="text" class="form-control" list="datalistOptions" placeholder="Type to search..." var="element">
  <datalist id="datalistOptions" var="datalist">
    <option value="" repeat="option"></option>
  </datalist>
  <div class="invalid-feedback" choice="error"></div>
  <div class="form-text text-secondary" choice="notes"></div>
</div>
HTML;
        return Template::load($html);
    }
}