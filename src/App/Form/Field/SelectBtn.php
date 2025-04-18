<?php
namespace App\Form\Field;

use Dom\Renderer\DisplayInterface;
use Dom\Renderer\RendererInterface;
use Dom\Renderer\Traits\RendererTrait;
use Dom\Template;
use Tk\Form\Field\Select;
use Tk\Ui\Attributes;

/**
 *
 */
class SelectBtn extends Select implements DisplayInterface, RendererInterface
{
    use RendererTrait;

    protected string $btnText = '';
    protected Attributes $btnAttr;

    public function __construct(string $name, array $optionList = [], string $type = self::TYPE_SELECT)
    {
        parent::__construct($name, $optionList, $type);
        $this->btnAttr = new Attributes();
    }

    public function setBtnText(string $text): static
    {
        $this->btnText = $text;
        return $this;
    }

    public function getBtnText(): string
    {
        return $this->btnText;
    }

    public function getBtnAttr(): Attributes
    {
        return $this->btnAttr;
    }

    public function addBtnCss(string $css): static
    {
        $this->btnAttr->addCss($css);
        return $this;
    }

    public function setBtnAttr(array|string $name, ?string $value = null): static
    {
        $this->btnAttr->setAttr($name, $value);
        return $this;
    }

    public function show(): ?Template
    {
        $t = $this->getTemplate();

        // Render standard select box
        $renderer = new \Tk\Form\Renderer\Dom\Field\Select($this);
        $renderer->setTemplate($t);
        $renderer->show();

        if ($this->getBtnText()) {
            $t->appendHtml('button', $this->getBtnText());
        }
        $t->setAttr('button', $this->getBtnAttr()->getAttrList());
        $t->addCss('button', $this->getBtnAttr()->getCssString());

        return $t;
    }

    public function __makeTemplate(): Template
    {
        $html = <<<HTML
<div var="field" class="tpl-form-select-btn">
  <label class="form-label" var="label"></label>
  <div class="input-group input-group-merge" var="is-error input-group">
    <select class="form-select" var="element">
      <optgroup repeat="optgroup" label="">
        <option repeat="option"></option>
      </optgroup>
      <option repeat="option"></option>
    </select>
    <a class="btn btn-white" target="_blank" type="button" var="button"></a>
  </div>
  <div class="invalid-feedback" choice="error"></div>
  <div class="form-text text-secondary" choice="notes"></div>
</div>
HTML;
        return Template::load($html);
    }
}