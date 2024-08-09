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

class ManagerPhp extends ControllerDomInterface
{
    use SystemTrait;

    protected ?Form $form = null;
    protected ?Table $table = null;
    protected ?Table\PhpRenderer $renderer = null;

    public function doDefault(Request $request): void
    {
        $this->getPage()->setTitle('Widget Manager');
        $this->getCrumbs()->reset();

        // create table
        $this->table = new Table();
        $this->table->setLimit($request->get($this->table->makeInstanceKey(Table::PARAM_LIMIT), 10));
        $this->table->setPage($request->get($this->table->makeInstanceKey(Table::PARAM_PAGE), 1));
        //$this->table->setOrderBy($request->get($this->table->makeInstanceKey(Table::PARAM_ORDERBY), 'name'));


        $rowSelect = Cell\RowSelect::create('id', 'widgetId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('widgetId')
            ->setSortable(true)
            ->addCss('text-center')->setHeader('ID');

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


        $filter = DbFilter::create([], $this->table->getOrderBy(), $this->table->getLimit(), $this->table->getOffset());
        $rows = Widget::getFiltered($filter);
        $this->table->setTotalRows(Db::getLastStatement()->getTotalRows());

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
                    $rows = Widget::getFiltered(DbFilter::create(['id' => $selected]));
                } else {
                    $rows = Widget::getFiltered($filter->resetLimits());
                }
                return $rows;
            });


        $this->table->execute($request);

        $path = $this->getConfig()->makePath(
            $this->getConfig()->get('path.vendor.org').'/tk-framework/Tt/Table/templates/bs5_php.php'
        );
        $this->renderer = new Table\PhpRenderer($this->table, $rows, $path);
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        // PHP Renderer
        $template->appendHtml('content', $this->renderer->getHtml());

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