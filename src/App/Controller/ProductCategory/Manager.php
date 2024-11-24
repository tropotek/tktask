<?php
namespace App\Controller\ProductCategory;

use App\Db\ProductCategory;
use App\Db\TaskCategory;
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
        $this->getPage()->setTitle('Product Category Manager');

        $this->setAccess(User::PERM_SYSADMIN);

        // init table
        $this->table = new \Bs\Mvc\Table();
        $this->table->setOrderBy('order_by');
        $this->table->setLimit(25);

        $this->table->appendCell(Cell\OrderBy::create('orderBy', ProductCategory::class));

        $rowSelect = RowSelect::create('id', 'productId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('name')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addHeaderCss('max-width')
            ->addOnValue(function(\App\Db\ProductCategory $obj, Cell $cell) {
                $url = Uri::create('/productCategoryEdit', ['productCategoryId' => $obj->productCategoryId]);
                return sprintf('<a href="%s">%s</a>', $url, $obj->name);
            });

        $this->table->appendCell('description')
            ->addCss('text-nowrap');

        $this->table->appendCell('created')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\DateFmt::onValue');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');


        // Add Table actions
        $this->table->appendAction(Csv::create())
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnCsv(function(Csv $action, array $selected) {
                $action->setExcluded(['id', 'actions']);
                $filter = $this->table->getDbFilter();
                if ($selected) {
                    $rows = ProductCategory::findFiltered($filter);
                } else {
                    $rows = ProductCategory::findFiltered($filter->resetLimits());
                }
                return $rows;
            });

        // execute table to init filter object
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $rows = ProductCategory::findFiltered($filter);
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
      <a href="#" title="Create Product Category" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Product Category</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i class="fa fa-folder-open"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}