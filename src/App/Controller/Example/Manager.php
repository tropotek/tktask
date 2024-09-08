<?php
namespace App\Controller\Example;

use App\Db\Example;
use Bs\ControllerAdmin;
use Bs\Table;
use Dom\Template;
use Tk\Alert;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Traits\SystemTrait;
use Tk\Uri;
use Tk\Db;
use Tt\Table\Action\Csv;
use Tt\Table\Action\Delete;
use Tt\Table\Cell;
use Tt\Table\Cell\RowSelect;

class Manager extends ControllerAdmin
{
    use SystemTrait;

    protected ?Table $table = null;


    public function doDefault(): void
    {
        $this->getPage()->setTitle('Example Manager');
        $this->getCrumbs()->reset();

        if (isset($_GET['del'])) {
            $ex = Example::find(intval($_GET['del'] ?? 0));
            if ($ex) {
                $ex->delete();
                Alert::addSuccess('Example removed successfully.');
            }
            Uri::create()->remove('del')->redirect();
        }

        // Create table
        $this->table = new Table('eg', 'name');

        // Add cells
        $rowSelect = RowSelect::create('id', 'exampleId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('actions')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(Example $row, Cell $cell) {
                $ed = Uri::create('/exampleEdit', ['exampleId' => $row->exampleId]);
                $del = Uri::create()->set('del', $row->exampleId);
                return <<<HTML
                    <a class="btn btn-primary" href="$ed" title="Edit"><i class="fa fa-fw fa-edit"></i></a> &nbsp;
                    <a class="btn btn-danger" href="$del" title="Delete" data-confirm="Are you sure you want to delete this record"><i class="fa fa-fw fa-trash"></i></a>
                HTML;
            });

        $this->table->appendCell('name')
            ->addHeaderCss('max-width')
            ->setSortable(true)
            ->addOnValue(function(Example $row, Cell $cell) {
                $url = Uri::create('/exampleEdit', ['exampleId' => $row->exampleId]);
                return sprintf('<a href="%s">%s</a>', $url, $row->name);
            });

        $this->table->appendCell('image')
            ->setSortable(true);
//            ->addOnValue(function(Example $row, Cell $cell) {
//                $url = Uri::create('/exampleEdit', ['exampleId' => $row->exampleId]);
//                return sprintf('<a href="%s">%s</a>', $url, $row->name);
//            });

        $this->table->appendCell('active')
            ->setSortable(true)
            ->addOnValue('\Tt\Table\Type\Boolean::onValue');

        $this->table->appendCell('modified')
            ->setSortable(true)
            ->addCss('text-nowrap text-center')
            ->addOnValue('\Tt\Table\Type\DateFmt::onValue');

        $this->table->appendCell('created')
            ->setSortable(true)
            ->addCss('text-nowrap text-center')
            ->addOnValue('\Tt\Table\Type\DateFmt::onValue');

        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))->setAttr('placeholder', 'Search name, id');

        $list = ['-- Active --' => '', 'Yes' => '1', 'No' => '0'];
        $this->table->getForm()->appendField(new Select('active', $list))->setStrict(true);

        // init filter fields for actions to access to the filter values
        $this->table->initForm();

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
                    $rows = Example::findFiltered($filter);
                } else {
                    $rows = Example::findFiltered($filter->resetLimits());
                }
                return $rows;
            });

        // execute actions and set table orderBy from request
        $this->table->execute();

        $rows = Example::findFiltered($this->table->getDbFilter());
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());

    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->appendText('title', $this->getPage()->getTitle());
        $template->setAttr('back', 'href', $this->getBackUrl());

        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
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