<?php
namespace App\Ui;

use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\CallbackCollection;
use Tk\Collection;
use Tk\Traits\SystemTrait;
use Tk\Ui\Button;
use Tk\Ui\Traits\AttributesTrait;
use Tk\Ui\Traits\CssTrait;

/**
 * This class uses the bootstrap dialog box model
 * @link http://getbootstrap.com/javascript/#modals
 *
 * To create the dialog:
 *
 *   $dialog = Dialog::create('myDialog', 'My Dialog Title');
 *   $dialog->setOnInit(function ($dialog) { ... });
 *   $dialog->setOnShow(function ($dialog) { $template = $dialog->getTemplate(); });
 *   ...
 *   $dialog->init();                   // Optional
 *   ...
 *   $dialog->execute($request);        // Optional
 *   ...
 *   $template->appendBodyTemplate($dialog->show());
 *
 * To add a close button to the footer:
 *
 *    $dialog->getButtonList()->append(\Tk\Ui\Button::createButton('Close')->setAttr('data-dismiss', 'modal'));
 *
 * Launch Button:
 *
 *    <a href="#" data-bs-toggle="modal" data-bs-target="#{id}"><i class="fa fa-info-circle"></i> {title}</a>
 *
 *    $template->setAttr('modelBtn', 'data-toggle', 'modal');
 *    $template->setAttr('modelBtn', 'data-target', '#'.$this->dialog->getId());
 *
 * @author Tropotek <http://www.tropotek.com/>
 */
class Dialog extends \Dom\Renderer\Renderer
{
    use AttributesTrait;
    use CssTrait;
    use SystemTrait;

    protected string $id = '';

    protected string $title = '';

    protected string $sizeCss = '';

    protected Collection $buttonList;

    protected string|Template $content = '';

    protected CallbackCollection $onInit;

    protected CallbackCollection $onExecute;

    protected CallbackCollection $onShow;


    public function __construct(string $title, string $dialogId = '')
    {
        $this->onInit     = new CallbackCollection();
        $this->onExecute  = new CallbackCollection();
        $this->onShow     = new CallbackCollection();
        $this->buttonList = new Collection();

        $this->setTitle($title);

        if (!$dialogId) {
            $dialogId = strtolower(preg_replace('/[^a-z0-9_-]/i', '-', $title));
        }
        $this->setId($dialogId);

        $this->setAttr('aria-labelledby', $this->getId().'-Label');
        //$this->getButtonList()->append(\Tk\Ui\Button::createButton('Close')->setAttr('data-dismiss', 'modal'));
    }


    public static function create(string $title, string $dialogId = ''): static
    {
        return new static($title, $dialogId);
    }

    /**
     * ensure the id is unique
     */
    protected function setId($id): static
    {
        static $instances = [];
        if ($this->getId()) return $this;
        if (!isset($instances[$id])) {
            $instances[$id] = 0;
        } else {
            $instances[$id]++;
        }
        if ($instances[$id] > 0) $id = $id.$instances[$id];
        $this->id = $id;
        $this->setAttr('id', $this->getId());
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return Collection|Button[]
     */
    public function getButtonList(): Collection
    {
        return $this->buttonList;
    }

    /**
     * Add Button helper function
     */
    public function addButton(string $name, string $icon = ''): Button
    {
        $btn = new Button($name, $icon);
        $btn->setAttr('name', $name);
        $btn->setAttr('id', $this->getId() . '-' . preg_replace('/[^a-z0-9]/i', '_', $name));
        if (strtolower($name) == 'close' || strtolower($name) == 'cancel') {
            $btn->setAttr('data-bs-dismiss', 'modal');
        }
        $this->getButtonList()->append($name, $btn);
        return $btn;
    }

    public function setLarge(): static
    {
        $this->sizeCss = 'modal-lg';
        return $this;
    }

    public function getSizeCss(): string
    {
        return $this->sizeCss;
    }

    public function setSizeCss(string $sizeCss): static
    {
        $this->sizeCss = $sizeCss;
        return $this;
    }

    public function getContent(): string|Template
    {
        return $this->content;
    }

    public function setContent(string|Template $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getOnInit(): CallbackCollection
    {
        return $this->onInit;
    }

    /**
     * function (Dialog $dialog) { }
     */
    public function addOnInit(callable $callable, int $priority = CallbackCollection::DEFAULT_PRIORITY): static
    {
        $this->getOnInit()->append($callable, $priority);
        return $this;
    }

    public function getOnExecute(): CallbackCollection
    {
        return $this->onExecute;
    }

    /**
     * function (Dialog $dialog, Request $request) { }
     */
    public function addOnExecute(callable $callable, int $priority = CallbackCollection::DEFAULT_PRIORITY): static
    {
        $this->getOnExecute()->append($callable, $priority);
        return $this;
    }

    public function getOnShow(): CallbackCollection
    {
        return $this->onShow;
    }

    /**
     * function (Dialog $dialog) { }
     */
    public function addOnShow(callable $callable, int $priority = CallbackCollection::DEFAULT_PRIORITY): static
    {
        $this->getOnShow()->append($callable, $priority);
        return $this;
    }


    public function init()
    {
        $this->getOnInit()->execute($this);
    }

    public function execute(Request $request)
    {
        $this->getOnExecute()->execute($this, $request);
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $this->getOnShow()->execute($this);

        if ($this->getSizeCss()) {
            $this->addCss($this->getSizeCss());
        }

        foreach ($this->getButtonList() as $btn) {
            $template->appendTemplate('footer', $btn->show());
        }

        $template->setText('title', $this->getTitle());
        $template->setAttr('title', 'id', $this->getId().'-Label');

        if ($this->getContent() instanceof \Dom\Template) {
            $template->appendTemplate('content', $this->getContent());
        } else if ($this->getContent()) {
            $template->appendHtml('content', $this->getContent());
        }

        // Add attributes
        $template->setAttr('dialog', $this->getAttrList());
        $template->addCss('dialog', $this->getCssList());

        return $template;
    }


    public function __makeTemplate(): ?Template
    {
        $xhtml = <<<HTML
<div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" aria-labelledby="_exampleModalLabel" var="dialog">
  <div class="modal-dialog" role="document" var="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="_exampleModalLabel" var="title"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" var="content"></div>
      <div class="modal-footer" var="footer"></div>
    </div>
  </div>
</div>
HTML;
        return $this->getFactory()->loadTemplate($xhtml);
    }

}
