<?php
namespace App\Controller\Recurring;

use App\Db\Recurring;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Alert;
use Tk\Form\Field\Input;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;
use Tk\Table\Action\Csv;
use Tk\Table\Action\Delete;
use Tk\Table\Action\Select;
use Tk\Uri;
use Tk\Db;

class Manager extends ControllerAdmin
{
    protected ?Table $table = null;

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Recurring Manager');

        if (!User::getAuthUser()?->isStaff()) {
            Alert::addWarning('You do not have permission to access this page');
                User::getAuthUser()?->getHomeUrl()->redirect() ?? Uri::create('/')->redirect();
        }

        // init table
        $this->table = new Table();
        $this->table->setOrderBy('next_on');
        $this->table->setLimit(25);

        $rowSelect = RowSelect::create('id', 'recurringId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('description')
            ->setSortable(true)
            ->addHeaderCss('max-width')
            ->addOnValue(function(Recurring $obj, Cell $cell) {
                $url = Uri::create('/recurringEdit', ['recurringId' => $obj->recurringId]);
                return sprintf('<a href="%s">%s</a>', $url, $obj->description);
            });

        $this->table->appendCell('productId')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue(function(Recurring $obj, Cell $cell) {
                return $obj->getProduct()->name;
            });

        $this->table->appendCell('price')
            ->addCss('text-nowrap text-end')
            ->setSortable(true);

        $this->table->appendCell('cycle')
            ->addCss('text-nowrap text-center')
            ->setSortable(true);

        $this->table->appendCell('nextOn')
            ->addCss('text-nowrap text-center')
            ->setHeader('Next Invoice')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::onValue');

        $this->table->appendCell('active')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        $this->table->appendCell('issue')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        $this->table->appendCell('startOn')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::onValue');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');


        // Add Table actions
        $this->table->appendAction(Delete::create()
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnDelete(function(Delete $action, array $selected) {
                foreach ($selected as $recurring_id) {
                    Db::delete('recurring', compact('recurring_id'));
                }
            }));

        $this->table->appendAction(Select::create('Active Status', 'fa fa-fw fa-times')
            ->setActions(['Active' => 'active', 'Disable' => 'disable'])
            ->setConfirmStr('Toggle active/disable on the selected rows?')
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnSelect(function(Select $action, array $selected, string $value) {
                foreach ($selected as $id) {
                    $obj = Recurring::find($id);
                    $obj->active = (strtolower($value) == 'active');
                    $obj->save();
                }
            })
        );

        $this->table->appendAction(Csv::create()
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnCsv(function(Csv $action, array $selected) {
                $action->setExcluded(['id', 'actions']);
                $filter = $this->table->getDbFilter();
                $this->table->getCell('description')->getOnValue()->reset();
                if ($selected) {
                    $rows = Recurring::findFiltered($filter);
                } else {
                    $rows = Recurring::findFiltered($filter->resetLimits());
                }
                return $rows;
            }));

        // execute table
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $rows = Recurring::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
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
      <a href="/recurringEdit" title="Create Recurring" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Recurring</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i class="far fa-money-bill-alt"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}