<?php
namespace App\Controller\Product;

use App\Db\Product;
use App\Db\ProductCategory;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Collection;
use Tk\Form\Field\Input;
use Tk\Table\Action\Csv;
use Tk\Table\Action\Select;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;
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
        $this->getPage()->setTitle('Product Manager', 'fa fa-shopping-cart');

        $this->setUserAccess(User::PERM_SYSADMIN);

        // init table
        $this->table = new Table();
        $this->table->setOrderBy('name');
        $this->table->setLimit(25);

        $rowSelect = RowSelect::create('id', 'productId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('name')
            ->setSortable(true)
            ->addCss('text-nowrap')
            ->addHeaderCss('max-width')
            ->addOnHtml(function(Product $obj, Cell $cell) {
                $url = Uri::create('/productEdit', ['productId' => $obj->productId]);
                return sprintf('<a href="%s">%s</a>', $url, $obj->name);
            });

        $this->table->appendCell('expenseCategoryId')
            ->setSortable(true)
            ->addCss('text-nowrap')
            ->addOnValue(function(Product $obj, Cell $cell) {
                return $obj->getProductCategory()->name;
            });

        $this->table->appendCell('cycle')
            ->setHeader('Recurring')
            ->setSortable(true)
            ->addCss('text-nowrap text-center');

        $this->table->appendCell('code')
            ->setSortable(true)
            ->addCss('text-nowrap text-center');

        $this->table->appendCell('price')
            ->setSortable(true)
            ->addCss('text-nowrap text-end');

        $this->table->appendCell('active')
            ->setSortable(true)
            ->addCss('text-nowrap text-center')
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        $this->table->appendCell('modified')
            ->setSortable(true)
            ->addCss('text-nowrap text-center')
            ->addOnValue('\Tk\Table\Type\Date::getLongDateTime');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        $this->table->getForm()->appendField((new \Tk\Form\Field\Select('active', ['' => '-- All --', 'y' => 'Active', 'n' => 'Inactive'])))
            ->setValue('y');

        $cats = ProductCategory::findFiltered(Db\Filter::create([], 'name'));
        $list = Collection::toSelectList($cats, 'productCategoryId');
        $this->table->getForm()->appendField((new \Tk\Form\Field\Select('productCategoryId', $list))
            ->prependOption('-- Category --', ''));

        $this->table->getForm()->appendField((new \Tk\Form\Field\Select('cycle', Product::CYCLE_LIST))
            ->prependOption('-- Recurring --', ''));


        // Add Table actions
        $this->table->appendAction(Select::create('Active Status', 'fa fa-fw fa-times')
            ->setActions(['Active' => 'active', 'Disable' => 'disable'])
            ->setConfirmStr('Toggle active/disable on the selected rows?')
            ->addOnExecute(function(Select $action) use ($rowSelect) {
                if (!isset($_POST[$action->getRequestKey()])) return;
                $active = trim(strtolower($_POST[$action->getRequestKey()] ?? 'active')) == 'active';
                $selected = $rowSelect->getSelected();
                foreach ($selected as $id) {
                    $obj = Product::find((int)$id);
                    $obj->active = $active;
                    $obj->save();
                }
            }));

        $this->table->appendAction(Csv::create()
            ->addOnExecute(function(Csv $action) {
                if (!$this->table->getCell(Product::getPrimaryProperty())) {
                    $this->table->prependCell(Product::getPrimaryProperty())->setHeader('id');
                }
                $filter = $this->table->getDbFilter()->resetLimits();
                return Product::findFiltered($filter);
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
        $template->appendText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-body" var="actions">
      <a href="/productEdit" title="Create Product" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Product</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i var="icon"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}