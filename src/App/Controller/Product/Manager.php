<?php
namespace App\Controller\Product;

use App\Db\Product;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Form\Field\Input;
use Tk\Table\Action\Csv;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;
use Tk\Table\Action\Delete;
use Tk\Uri;
use Tk\Db;

/**
 *
 */
class Manager extends ControllerAdmin
{
    protected ?Table $table = null;

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Product Manager');

        $this->setAccess(User::PERM_SYSADMIN);

        // init table
        $this->table = new Table();
        $this->table->setOrderBy('order_by');
        $this->table->setLimit(25);

        $this->table->appendCell(Cell\OrderBy::create('orderBy', Product::class));

        $rowSelect = RowSelect::create('id', 'productId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('name')
            ->addCss('text-nowrap')
            ->addHeaderCss('max-width')
            ->addOnValue(function(Product $obj, Cell $cell) {
                $url = Uri::create('/productEdit', ['productId' => $obj->productId]);
                return sprintf('<a href="%s">%s</a>', $url, $obj->name);
            });

        $this->table->appendCell('categoryId')
            ->addCss('text-nowrap');

        $this->table->appendCell('recur')
            ->addCss('text-nowrap');

        $this->table->appendCell('code')
            ->addCss('text-nowrap');

        $this->table->appendCell('price')
            ->addCss('text-nowrap');

        $this->table->appendCell('active')
            ->addCss('text-nowrap')
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        $this->table->appendCell('modified')
            ->addCss('text-nowrap')
            ->addOnValue('\Tk\Table\Type\DateFmt::onValue');

        $this->table->appendCell('created')
            ->addCss('text-nowrap')
            ->addOnValue('\Tk\Table\Type\DateFmt::onValue');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');


        // Add Table actions
        $this->table->appendAction(\Tk\Table\Action\Select::create('Active Status', 'fa fa-fw fa-times')
            ->setActions(['Active' => 'active', 'Disable' => 'disable'])
            ->setConfirmStr('Toggle active/disable on the selected rows?')
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnSelect(function(\Tk\Table\Action\Select $action, array $selected, string $value) {
                foreach ($selected as $id) {
                    $obj = Product::find($id);
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
                if ($selected) {
                    $rows = Product::findFiltered($filter);
                } else {
                    $rows = Product::findFiltered($filter->resetLimits());
                }
                return $rows;
            }));

        // execute table to init filter object
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $rows = Product::findFiltered($filter);
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
      <a href="#" title="Create Product" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Product</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-shopping-cart"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}