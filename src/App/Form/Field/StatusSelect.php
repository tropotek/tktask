<?php
namespace App\Form\Field;

use Dom\Renderer\DisplayInterface;
use Dom\Renderer\RendererInterface;
use Dom\Renderer\Traits\RendererTrait;
use Dom\Template;
use Tk\Form\Field\Select;

/**
 * This field is a select with a checkbox.
 * The checkbox state is not saved, and is reset to the default value
 * on each page load. it is meant to be used as a trigger element.
 */
class StatusSelect extends Select implements DisplayInterface, RendererInterface
{
    use RendererTrait;

    protected bool   $checkboxValue = true;
    protected string $checkboxName  = '';
    protected string $notesName     = '';
    protected string $notesValue    = '';


    public function __construct(string $name, array $optionList = [])
    {
        parent::__construct($name, $optionList);
        $this->checkboxName = $name . '_notify';
        $this->notesName = $name . '_msg';
    }

    public function getNotesName(): string
    {
        return $this->notesName;
    }

    public function getNotesValue(): string
    {
        return $this->notesValue;
    }

    public function setNotesValue(string $str): static
    {
        $this->notesValue = $str;
        return $this;
    }

    public function getCheckboxName(): string
    {
        return $this->checkboxName;
    }

    public function isChecked(): bool
    {
        return $this->checkboxValue;
    }

    public function setChecked(bool $b): static
    {
        $this->checkboxValue = $b;
        return $this;
    }

    public function show(): ?Template
    {
        $t = $this->getTemplate();

        // Render standard select box
        $renderer = new \Tk\Form\Renderer\Dom\Field\Select($this);
        $renderer->setTemplate($t);
        $renderer->show();

        $t->setAttr('checkbox', 'name', $this->getCheckboxName());
        $t->setAttr('shadow', 'name', $this->getCheckboxName());
        $t->setAttr('checkbox', 'aria-label', $this->getCheckboxName());
        $t->setAttr('msg', 'name', $this->getNotesName());
        if ($this->isChecked()) {
            $t->setAttr('checkbox', 'checked', 'checked');
        }

        // See the app.js for this code....
        $js = <<<JS
jQuery(function ($) {

    tkRegisterInit(function () {
      $('.tk-status-select', this).each(function () {
        var select = $('select', this);
        var msg = $('textarea', this);
        var cb = $('[type="checkbox"]', this);

        select.data('cs-current-val', select.val());
        msg.hide();
        cb.prop('checked', false);

        select.on('change', function () {
          if ($(this).val() === $(this).data('cs-current-val')) {
            cb.prop('checked', false);
            if (select.data('messageText') !== 'off')
              msg.hide();
          } else {
            cb.prop('checked', true);
            if (select.data('messageText') !== 'off')
              msg.show();
          }

          if (select.data('messageText') !== 'off') {
            $(this).blur();
            msg.focus();
          }
        });
      });

    });

});
JS;
        $t->appendJs($js);
        return $t;
    }

    public function __makeTemplate(): Template
    {
        $html = <<<HTML
<div var="field" class="tk-status-select">
  <label class="form-label" var="label"></label>
  <div class="input-group">
    <div class="input-group-text">
      <input type="hidden" value="0" var="shadow">
      <input type="checkbox" title="Check To Send Notifications" class="form-check-input mt-0" value="1" var="checkbox">
    </div>
    <select class="form-select" var="element">
      <optgroup repeat="optgroup">
        <option repeat="option"></option>
      </optgroup>
      <option repeat="option"></option>
    </select>
  </div>
  <div class="status-notes" style="margin-top: -2px;">
    <textarea name="msg" class="form-control" placeholder="Enter A Message..." var="msg"></textarea>
  </div>
  <div class="form-text text-secondary" choice="notes"></div>
  <div class="invalid-feedback" choice="error"></div>
</div>
HTML;
        return Template::load($html);
    }
}