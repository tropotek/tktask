<?php
namespace App\Controller\Domain;

use App\Db\Company;
use App\Db\Domain;
use App\Db\ExpenseCategory;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Collection;
use Tk\FileUtil;
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
        //$this->setUserAccess(User::PERM_SYSADMIN);
        $this->getPage()->setTitle('Domain Manager', 'fa fa-cogs');

        // init table
        $this->table = new Table('domain');
        $this->table->setOrderBy('domain_id');
        $this->table->setLimit(25);

        $rowSelect = RowSelect::create('id', 'domainId');
        $this->table->appendCell($rowSelect);

//        $this->table->appendCell('actions')
//            ->addCss('text-nowrap text-center')
//            ->addOnValue(function(Domain $obj, Cell $cell) {
//                $url = Uri::create('/domainEdit')->set('domainId', $obj->domainId);
//                return <<<HTML
//                    <a class="btn btn-outline-success" href="$url" title="Edit"><i class="fa fa-fw fa-edit"></i></a>
//                HTML;
//            });

        $this->table->appendCell('status')
            ->setSortable(true)
            ->addCss('text-center')
            ->addOnValue(function(Domain $obj, Cell $cell) {
                if ($obj->status) {
                    return '<span class="badge bg-success">Online</span>';
                } else {
                    return '<span class="badge bg-danger">Offline</span>';
                }
            });

        $this->table->appendCell('url')
            ->addCss('full-width')
            ->addHeaderCss('text-start')
            ->setSortable(true)
            ->addOnValue(function(Domain $obj, Cell $cell) {
                $url = Uri::create('/domainEdit')->set('domainId', $obj->domainId);
                return <<<HTML
                    <a href="$url" title="Edit">{$obj->url}</a>
                HTML;
            });

        $this->table->appendCell('companyName')
            ->setHeader('Company')
            ->addCss('text-nowrap')
            ->addHeaderCss('text-start')
            ->setSortable(true);

        $this->table->appendCell('siteName')
            ->setHeader('Site')
            ->addCss('text-nowrap')
            ->addHeaderCss('text-start')
            ->setSortable(true);

        $this->table->appendCell('bytes')
            ->setHeader('HDD')
            ->addCss('text-nowrap')
            ->addHeaderCss('text-start')
            ->setSortable(true)
            ->addOnValue(function(Domain $obj, Cell $cell) {
                if ($obj->bytes <= 0) return '';
                return FileUtil::bytes2String($obj->bytes);
            });

        $this->table->appendCell('lastPingTime')
            ->setHeader('Pinged')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\DateTime::onValue');

        $this->table->appendCell('active')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        $this->table->getForm()->appendField((new \Tk\Form\Field\Select('active', ['' => '-- All --', 'y' => 'Active', 'n' => 'Inactive'])))
            ->setValue('y');

        $cats = Company::findFiltered(Db\Filter::create(['type' => Company::TYPE_CLIENT, 'active' => true], 'name'));
        $list = Collection::toSelectList($cats, 'companyId', fn($obj) => ($obj->active ? '' : '- ') . $obj->name);
        $this->table->getForm()->appendField((new \Tk\Form\Field\Select('companyId', $list))
            ->prependOption('-- Company --', ''));



        // Add Table actions
        $this->table->appendAction(Delete::create()
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnDelete(function(Delete $action, array $selected) {
                foreach ($selected as $domain_id) {
                    Db::delete('domain', compact('domain_id'));
                }
            }));

        $this->table->appendAction(Select::create('Active Status', 'fa fa-fw fa-times')
            ->setActions(['Active' => 'active', 'Disable' => 'disable'])
            ->setConfirmStr('Toggle active/disable on the selected rows?')
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnSelect(function(Select $action, array $selected, string $value) {
                foreach ($selected as $id) {
                    $obj = Domain::find($id);
                    $obj->active = (strtolower($value) == 'active');
                    $obj->save();
                }
            })
        );

//        $this->table->appendAction(Csv::create()
//            ->addOnCsv(function(Csv $action) {
//                $action->setExcluded(['actions']);
//                if (!$this->table->getCell(Domain::getPrimaryProperty())) {
//                    $this->table->prependCell(Domain::getPrimaryProperty())->setHeader('id');
//                }
//                //$this->table->getCell('name')->getOnValue()->reset();
//                $filter = $this->table->getDbFilter()->resetLimits();
//                return Domain::findFiltered($filter);
//            }));

        // execute table
        $this->table->execute();

        // todo: remove cell orderBy validation before release
//        if (!$this->table->validateCells(Domain::getDataMap())) {
//            $this->table->getTableSession()->remove($this->table->makeRequestKey(Table::PARAM_ORDERBY));
//        }

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $rows = Domain::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());
        $template->addCss('icon', $this->getPage()->getIcon());

        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="page-actions card mb-3">
    <div class="card-body">
      <a href="/domainEdit" title="Create Domain" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Domain</a>
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