<?php
namespace App\Controller\Widget;

use App\Db\Widget;
use Bs\ControllerDomInterface;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Form;
use Tk\Traits\SystemTrait;
use Tk\Uri;
use Tt\Db;
use Tt\DbFilter;
use Tt\Table;
use Tt\Table\Cell;

class ManagerPlain extends ControllerDomInterface
{
    use SystemTrait;

    protected ?Form $form = null;
    protected ?Table $table = null;
    protected ?Table\DomRenderer $renderer = null;

    public function doDefault(Request $request): void
    {
        $this->getPage()->setTitle('Widget Manager');
        $this->getCrumbs()->reset();


        // create table
        $this->table = new Table();
        $this->table->setLimit($request->query->get($this->table->makeRequestKey(Table::PARAM_LIMIT), 10));
        $this->table->setPage($request->query->get($this->table->makeRequestKey(Table::PARAM_PAGE), 1));
        $this->table->setOrderBy($request->query->get($this->table->makeRequestKey(Table::PARAM_ORDERBY), 'name'));


        // create Form filter
        $this->form = new Form($this->table->getId().'f');
        $this->form->addCss('tk-table-filter');

        $this->form->appendField(new Form\Field\Input('search'))->setAttr('placeholder', 'Search name, id');

        $list = array_flip(\Bs\Db\User::TYPE_LIST);
        $this->form->appendField(new Form\Field\Select('type', $list))->prependOption('-- Type --', '');

        $this->form->appendField(new Form\Action\Submit('filter', function (Form $form, Form\Action\ActionInterface $action) {
            $values = $form->getFieldValues();
            $_SESSION[$this->table->makeRequestKey('filter')] = $values;
            Uri::create()->redirect();
        }))->setLabel('Search');
        $this->form->appendField(new Form\Action\Submit('clear', function (Form $form, Form\Action\ActionInterface $action) {
            unset($_SESSION[$this->table->makeRequestKey('filter')]);
            Uri::create()->redirect();
        }))->addCss('btn-outline-secondary');

        $this->form->execute($request->request->all());
        if (!$this->form->isSubmitted() && isset($_SESSION[$this->table->makeRequestKey('filter')])) {
            $this->form->setFieldValues($_SESSION[$this->table->makeRequestKey('filter')]);
        }

        // create DbFilter for csv export
        // no need to create this here if not using the CSV action (could be created when getting rows)
        $filter = DbFilter::createFromTable($this->form->getFieldValues(), $this->table);

        // Add cells
        $rowSelect = Cell\RowSelect::create('id', 'widgetId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('widgetId')
            ->setSortable(true)
            ->addCss('text-center')->setHeader('#');

        $this->table->appendCell('actions')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(Widget $row, Cell $cell) {
                $url = Uri::create('/widgetEdit', ['widgetId' => $row->widgetId]);
                $del = Uri::create('/widgetEdit', ['del' => $row->widgetId]);
                return <<<HTML
                    <a class="btn btn-primary" href="$url" title="Edit"><i class="fa fa-fw fa-edit"></i></a> &nbsp;
                    <a class="btn btn-danger" href="$del" title="Delete" data-confirm="Are you sure you want to delete 'Zuly'"><i class="fa fa-fw fa-trash"></i></a>
                HTML;
            });

        $this->table->appendCell('name')
            ->setSortable(true)
            ->addOnValue(function(Widget $row, Cell $cell) {
                $url = Uri::create('/widgetEdit', ['widgetId' => $row->widgetId]);
                return sprintf('<a href="%s">%s</a>', $url, $row->name);
            })
            ->getHeaderAttrs()->addCss('max-width');

        $this->table->appendCell('dateTime')
            ->addCss('text-nowrap text-center')
            ->addOnValue('\Tt\Table\Type\DateTime::onValue');

        $this->table->appendCell('modified')
            ->addCss('text-nowrap text-center')
            ->addOnValue('\Tt\Table\Type\Date::onValue');
        $this->table->appendCell('created')
            ->addCss('text-nowrap text-center')
            ->addOnValue('\Tt\Table\Type\Time::onValue');


        // Table actions
        $this->table->appendAction(Table\Action\Delete::create($rowSelect))
            ->addOnDelete(function(Table\Action\Delete $action, array $selected) {
                foreach ($selected as $widget_id) {
                    Db::delete('widget', compact('widget_id'));
                }
            });

        $this->table->appendAction(Table\Action\Csv::create($rowSelect))
            ->addOnCsv(function(Table\Action\Csv $action, array $selected) use ($filter) {
                $action->setExcluded(['id', 'actions']);
                $action->getTable()->getCell('name')->getOnValue()->reset();    // remove html from cell
                if (count($selected)) {
                    $rows = Widget::findFiltered($filter);
                } else {
                    $rows = Widget::findFiltered($filter->resetLimits());
                }
                return $rows;
            });


        // execute actions and set table orderBy from request
        $this->table->execute($request);



        $path = $this->getConfig()->makePath(
            $this->getConfig()->get('path.vendor.org').'/tk-framework/Tt/Table/templates/bs5_dom.html'
        );
        $this->renderer = new Table\DomRenderer($this->table, $path);
        // get rows using the DbFilter created above
//        $rows = Widget::getFiltered($filter);
//        $this->table->setTotalRows(Db::getLastStatement()->getTotalRows());
        $this->renderer->setRows(
            Widget::findFiltered($filter),
            Db::getLastStatement()->getTotalRows()
        );

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        // Dom Renderer
        $tplFile = $this->makePath($this->getConfig()->get('path.template.form.dom.inline'));
        $formRenderer = new Form\Renderer\Dom\Renderer($this->form, $tplFile);
        $template->appendTemplate('content', $formRenderer->show());

        $template->appendTemplate('content', $this->renderer->show());

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