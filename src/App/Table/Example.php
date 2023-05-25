<?php
namespace App\Table;

use App\Db\ExampleMap;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Traits\SystemTrait;
use Tk\Ui\Link;
use Tk\Uri;
use Tk\Form;
use Tk\Form\Field;
use Tk\FormRenderer;
use Tk\Table;
use Tk\Table\Cell;
use Tk\Table\Action;
use Tk\TableRenderer;

class Example
{
    use SystemTrait;

    protected Table $table;

    protected ?Form $filter = null;


    public function __construct()
    {
        $this->table = new Table('example');
        $this->filter = new Form($this->table->getId() . '-filters');
    }

    private function doDelete($id)
    {
        /** @var \App\Db\Example $ex */
        $ex = ExampleMap::create()->find($id);
        $ex?->delete();

        Alert::addSuccess('Example removed successfully.');
        Uri::create()->reset()->redirect();
    }

    public function doDefault(Request $request)
    {
        if ($request->query->has('del')) {
            $this->doDelete($request->query->get('del'));
        }

        $this->getTable()->appendCell(new Cell\Checkbox('id'));
        $this->getTable()->appendCell(new Cell\Text('actions'))->addOnShow(function (Cell\Text $cell) {
            $cell->addCss('text-nowrap text-center');
            $obj = $cell->getRow()->getData();

            $template = $cell->getTemplate();
            $btn = new Link('Edit');
            $btn->setText('');
            $btn->setIcon('fa fa-edit');
            $btn->addCss('btn btn-primary');
            $btn->setUrl(Uri::create('/exampleEdit')->set('id', $obj->getId()));
//            $btn->setUrl(Uri::create('/exampleEdit/' . $obj->getId()));
            $template->appendTemplate('td', $btn->show());
            $template->appendHtml('td', '&nbsp;');

            $btn = new Link('Delete');
            $btn->setText('');
            $btn->setIcon('fa fa-trash');
            $btn->addCss('btn btn-danger');
            $btn->setUrl(Uri::create()->set('del', $obj->getId()));
            $btn->setAttr('data-confirm', 'Are you sure you want to delete \''.$obj->getName().'\'');
            $template->appendTemplate('td', $btn->show());

        });

        $this->getTable()->appendCell(new Cell\Text('name'))
            ->setUrl(Uri::create('/exampleEdit'))->setAttr('style', 'width: 100%;')
            ->addOnShow(function (Cell\Text $cell) {
                $obj = $cell->getRow()->getData();
//                $cell->setUrlProperty('');  // Do this to disable the Url property
//                $cell->setUrl('/exampleEdit/'.$obj->getId());
            });

        $this->getTable()->appendCell(new Cell\Text('nick'))->setUrl(Uri::create('/exampleEdit'))
            ->addOnShow(function (Cell\Text $cell) {
                $obj = $cell->getRow()->getData();
                if ($obj->getNick() === null) {
                    // How to change the HTML display
                    $t = $cell->getTemplate();
                    $html = sprintf('<b>{{NULL}</b>');
                    $t->insertHtml('td', $html);
                    // How to set the css value
                    $cell->setValue('{null}');
                }
            });
        $this->getTable()->appendCell(new Cell\Text('image'));
        $this->getTable()->appendCell(new Cell\Boolean('active'));
        $this->getTable()->appendCell(new Cell\Text('modified'));
        $this->getTable()->appendCell(new Cell\Text('created'));


        // Table filters
        $this->getFilter()->appendField(new Field\Input('search'))->setAttr('placeholder', 'Search');
        // Load filter values
        $this->getFilter()->setFieldValues($this->getTable()->getTableSession()->get($this->getFilter()->getId(), []));
        $this->getFilter()->appendField(new Form\Action\Submit('Search', function (Form $form, Action\ActionInterface $action) {
            $this->getTable()->getTableSession()->set($this->getFilter()->getId(), $form->getFieldValues());
            Uri::create()->redirect();
        }))->setGroup('');
        $this->getFilter()->appendField(new Form\Action\Submit('Clear', function (Form $form, Action\ActionInterface $action) {
            $this->getTable()->getTableSession()->set($this->getFilter()->getId(), []);
            Uri::create()->redirect();
        }))->setGroup('')->addCss('btn-secondary');
        //
        $this->getFilter()->execute($request->request->all());


        // Table Actions
        if ($this->getConfig()->isDebug()) {
            $this->getTable()->appendAction(new Action\Link('reset', Uri::create()->set(Table::RESET_TABLE, $this->getTable()->getId()), 'fa fa-retweet'))
                ->setLabel('')
                ->setAttr('data-confirm', 'Are you sure you want to reset the Table`s session?')
                ->setAttr('title', 'Reset table filters and order to default.');
        }
        $this->getTable()->appendAction(new Action\Button('Create'))->setUrl(Uri::create('/exampleEdit'));
        $this->getTable()->appendAction(new Action\Delete());
        $this->getTable()->appendAction(new Action\Csv())->addExcluded('actions');

        // Query
        $tool = $this->getTable()->getTool();
        $filter = $this->getFilter()->getFieldValues();
        $list = ExampleMap::create()->findFiltered($filter, $tool);
        $this->getTable()->setList($list, $tool->getFoundRows());

        $this->getTable()->execute($request);
    }

    public function show(): ?Template
    {
        $renderer = new TableRenderer($this->getTable());
        //$renderer->setFooterEnabled(false);
        $this->getTable()->getRow()->addCss('text-nowrap');
        $this->getTable()->addCss('table-hover');

        if ($this->getFilter()) {
            $this->getFilter()->addCss('row gy-2 gx-3 align-items-center');
            $filterRenderer = FormRenderer::createInlineRenderer($this->getFilter());
            $renderer->getTemplate()->appendTemplate('filters', $filterRenderer->show());
            $renderer->getTemplate()->setVisible('filters');
        }

        return $renderer->show();
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function getFilter(): ?Form
    {
        return $this->filter;
    }
}