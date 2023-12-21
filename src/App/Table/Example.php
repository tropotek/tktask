<?php
namespace App\Table;

use App\Db\ExampleMap;
use Bs\Table\ManagerInterface;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Alert;
use Tk\Db\Mapper\Result;
use Tk\Db\Tool;
use Tk\Ui\Link;
use Tk\Uri;
use Tk\Form\Field;
use Tk\Table\Cell;
use Tk\Table\Action;

class Example extends ManagerInterface
{

    public function initCells(): void
    {
        $this->appendCell(new Cell\RowSelect('exampleId'));
        $this->appendCell(new Cell\Text('actions'))->addOnShow(function (Cell\Text $cell) {
            $cell->addCss('text-nowrap text-center');
            $obj = $cell->getRow()->getData();

            $template = $cell->getTemplate();
            $btn = new Link('Edit');
            $btn->setText('');
            $btn->setIcon('fa fa-edit');
            $btn->addCss('btn btn-primary');
            $btn->setUrl(Uri::create('/exampleEdit')->set('exampleId', $obj->getExampleId()));
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

        $this->appendCell(new Cell\Text('name'))
            ->setUrlProperty('exampleId')
            ->setUrl(Uri::create('/exampleEdit'))
            ->setAttr('style', 'width: 100%;')
            ->addOnShow(function (Cell\Text $cell) {
                $obj = $cell->getRow()->getData();
//                $cell->setUrlProperty('');  // Do this to disable the Url property
//                $cell->setUrl('/exampleEdit/'.$obj->getId());
            });

//        $this->appendCell(new Cell\Text('nick'))
//            ->setUrlProperty('exampleId')
//            ->setUrl(Uri::create('/exampleEdit'))
//            ->addOnShow(function (Cell\Text $cell) {
//                $obj = $cell->getRow()->getData();
//                if ($obj->getNick() === null) {
//                    // How to change the HTML display
//                    $t = $cell->getTemplate();
//                    $html = '{null}';
//                    $t->insertHtml('td', $html);
//                    // How to set the css value
//                    $cell->setValue($html);
//                }
//            });

        $this->appendCell(new Cell\Text('image'));
        $this->appendCell(new Cell\Boolean('active'));
        $this->appendCell(new Cell\Date('modified'));
        $this->appendCell(new Cell\Date('created'));

        // Table filters
        $this->getFilterForm()->appendField(new Field\Input('search'))->setAttr('placeholder', 'Search');
        $list = ['-- Active --' => '', 'Yes' => '1', 'No' => '0'];
        $this->getFilterForm()->appendField(new Field\Select('active', $list))->setStrict(true);

        // Table Actions
        $this->appendAction(new Action\Button('Create'))->setUrl(Uri::create('/exampleEdit'));
        $this->appendAction(new Action\Delete('delete', 'exampleId'));
        $this->appendAction(new Action\Csv('csv', 'exampleId'))->addExcluded('actions');
    }

    public function execute(Request $request): static
    {
        if ($request->query->has('del')) {
            /** @var \App\Db\Example $ex */
            $ex = ExampleMap::create()->find($request->query->getInt('del'));
            $ex?->delete();
            Alert::addSuccess('Example removed successfully.');
            Uri::create()->reset()->redirect();
        }

        parent::execute($request);
        return $this;
    }

    public function findList(array $filter = [], ?Tool $tool = null): null|array|Result
    {
        if (!$tool) $tool = $this->getTool('');
        $filter = array_merge($this->getFilterForm()->getFieldValues(), $filter);
        $list = ExampleMap::create()->findFiltered($filter, $tool);
        $this->setList($list);
        return $list;
    }

    public function show(): ?Template
    {
        $renderer = $this->getTableRenderer();
        $this->getRow()->addCss('text-nowrap');
        $this->showFilterForm();
        return $renderer->show();
    }

}