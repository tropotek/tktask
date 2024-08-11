<?php
namespace App\Controller\Widget;

use App\Db\Widget;
use Bs\ControllerDomInterface;
use Bs\Table;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Traits\SystemTrait;
use Tk\Uri;
use Tt\Db;
use Tt\Table\Action\Csv;
use Tt\Table\Action\Delete;
use Tt\Table\Cell;
use Tt\Table\Cell\RowSelect;

class ManagerBs extends ControllerDomInterface
{
    use SystemTrait;

    protected ?Table $table = null;

    public function doDefault(Request $request): void
    {
        $this->getPage()->setTitle('Widget Manager BS');
        $this->getCrumbs()->reset();


        // Create table
        $this->table = new Table('wtbl', 'name');

        // Add cells
        $rowSelect = RowSelect::create('id', 'widgetId');
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
                    <a class="btn btn-danger" href="$del" title="Delete" data-confirm="Are you sure you want to delete this record"><i class="fa fa-fw fa-trash"></i></a>
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
            ->addOnValue('\Tt\Table\Type\DateFmt::onValue');

        $this->table->appendCell('modified')
            ->addCss('text-nowrap text-center')
            ->addOnValue('\Tt\Table\Type\DateFmt::onValue');
        $this->table->appendCell('created')
            ->addCss('text-nowrap text-center')
            ->addOnValue('\Tt\Table\Type\DateFmt::onValue');


        // Add Filter Fields
        $this->table->getFilterForm()->appendField(new Input('search'))->setAttr('placeholder', 'Search name, id');

        $list = array_flip(\Bs\Db\User::TYPE_LIST);
        $this->table->getFilterForm()->appendField(new Select('type', $list))->prependOption('-- Type --', '');

        // init filter fields for actions to access to the filter values
        $this->table->init($request);

        // Add Table actions
        $this->table->appendAction(Delete::create($rowSelect))
            ->addOnDelete(function(Delete $action, array $selected) {
                foreach ($selected as $widget_id) {
                    Db::delete('widget', compact('widget_id'));
                }
            });

        $this->table->appendAction(Csv::create($rowSelect))
            ->addOnCsv(function(Csv $action, array $selected) {
                $action->setExcluded(['id', 'actions']);
                $action->getTable()->getCell('name')->getOnValue()->reset();    // remove html from cell
                $filter = $action->getTable()->getDbFilter();
                if (count($selected)) {
                    $rows = Widget::findFiltered($filter);
                } else {
                    $rows = Widget::findFiltered($filter->resetLimits());
                }
                return $rows;
            });

        // execute actions and set table orderBy from request
        $this->table->execute($request);

        $rows = Widget::findFiltered($this->table->getDbFilter());
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        // get rows and render table
        $template->appendTemplate('content', $this->table->show());

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