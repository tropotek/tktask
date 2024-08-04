<?php
namespace App\Controller\Widget;

use App\Db\Widget;
use Bs\ControllerDomInterface;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Form;
use Tk\Table;
use Tk\TableRenderer;
use Tk\Traits\SystemTrait;
use Tk\Uri;

class ManagerTk extends ControllerDomInterface
{
    use SystemTrait;

    protected ?Form $form = null;
    protected ?Table $table = null;

    public function doDefault(Request $request)
    {
        $this->getPage()->setTitle('Widget Manager');
        $this->getCrumbs()->reset();

        // create table
        $this->table = new Table('widget');
        $this->table->addCss('table-hover');

        // Add table cells
        $this->table->appendCell(new Table\Cell\Text('name'))
            ->setUrlProperty('widgetId')
            ->setUrl(Uri::create('/widgetEdit'))
            ->setAttr('style', 'width: 100%;');

        $this->table->appendCell(new Table\Cell\Boolean('active'));
        $this->table->appendCell(new Table\Cell\Date('modified'));
        $this->table->appendCell(new Table\Cell\Date('created'));


        // todo
//        // Table filters
//        $this->form->appendField(new Form\Field\Input('search'))->setAttr('placeholder', 'Search');
//        $list = ['-- Active --' => '', 'Yes' => '1', 'No' => '0'];
//        $this->form->appendField(new Form\Field\Select('active', $list))->setStrict(true);
//

//        if (count($this->form->getFields())) {
//            // Load filter values
//            $this->form->setFieldValues($this->table->getTableSession()->get($this->form->getId(), []));
//
//            $this->form->appendField(new Form\Action\Submit('Search', function (Form $form, Form\Action\ActionInterface $action) {
//                $this->table->getTableSession()->set($this->form->getId(), $form->getFieldValues());
//                Uri::create()->redirect();
//            }))->setGroup('');
//
//            $this->form->appendField(new Form\Action\Submit('Clear', function (Form $form, Form\Action\ActionInterface $action) {
//                $this->table->getTableSession()->set($this->form->getId(), []);
//                Uri::create()->redirect();
//            }))->setGroup('')->addCss('btn-outline-secondary');
//
//            $this->form->execute($this->getRequest()->request->all());
//        }

//        // Table Actions
//        $this->table->appendAction(new Table\Action\Button('Create'))->setUrl(Uri::create('/exampleEdit'));
//        $this->table->appendAction(new Table\Action\Delete('delete', 'exampleId'));
//        $this->table->appendAction(new Table\Action\Csv('csv', 'exampleId'))->addExcluded('actions');

        if ($this->getConfig()->isDebug()) {
            $this->table->prependAction(new Table\Action\Link('reset', Uri::create()->set(Table::RESET_TABLE, $this->table->getId()), 'fa fa-retweet'))
                ->setLabel('')
                ->setAttr('data-confirm', 'Are you sure you want to reset the Table`s session?')
                ->setAttr('title', 'Reset table filters and order to default.');
        }

        // TODO: how do we do this elegantly
        /*
        $list = $this->table->createList(); // create an empty table list obj
        $rows = $this->getFactory()->getDb()->query("
            SELECT *
            FROM v_widget
            WHERE active
            LIMIT :offset, :limit
            ORDER BY :orderBy :odir",
            [
                'offset' => 3,
                'limit' => 10,
                'orderBy'  => 'name',
                'odir'   => 'ASC'
            ],
            Widget::class
        );
        $list->set($rows);
        */

//        $this->table->setDbList(function(array $filters, int $limit, int $offset, string $orderBy) {
//            return [];
//        });

       // $this->table->setList($rows, $tool);

        $this->table->execute($request);

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $tableRenderer = new TableRenderer($this->table);
        //$tableRenderer->setFooterEnabled(false);

        if ($this->form && count($this->form->getFields())) {
            $this->form->addCss('row gy-2 gx-3 align-items-center');
            $filterRenderer = Form\Renderer\Dom\Renderer::createInlineRenderer($this->form);
            //$filterRenderer = Form\Renderer\Std\Renderer::createInlineRenderer($this->getFilterForm());
            $tableRenderer->getTemplate()->appendHtml('filters', $filterRenderer->show());
            $tableRenderer->getTemplate()->setVisible('filters');
        }

        $template->appendTemplate('content', $tableRenderer->show());

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