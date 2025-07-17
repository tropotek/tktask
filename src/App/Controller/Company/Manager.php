<?php
namespace App\Controller\Company;

use App\Db\Company;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Collection;
use Tk\Form\Field\Input;
use Tk\Form\Field\Select;
use Tk\Table\Action\Csv;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;
use Tk\Table\Action\Delete;
use Tk\Uri;
use Tk\Db;

class Manager extends ControllerAdmin
{
    protected ?Table $table = null;

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Company Manager', 'fa fa-building');

        $this->setUserAccess(User::PERM_SYSADMIN);

        // init table
        $this->table = new Table('companyMgr');
        $this->table->setOrderBy('name');
        $this->table->setLimit(25);

        $rowSelect = RowSelect::create('id', 'companyId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('name')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addHeaderCss('max-width')
            ->addOnHtml(function(Company $obj, Cell $cell) {
                $url = Uri::create('/companyEdit', ['companyId' => $obj->companyId]);
                return sprintf('<a href="%s">%s</a>', $url, $obj->name);
            });

        $this->table->appendCell('contact')
            ->addCss('text-nowrap')
            ->setSortable(true);

        $this->table->appendCell('phone')
            ->addCss('text-nowrap')
            ->setSortable(true);

        $this->table->appendCell('email')
            ->addCss('text-nowrap')
            ->setSortable(true);

        $this->table->appendCell('type')
            ->addCss('text-nowrap')
            ->setSortable(true);

        $this->table->appendCell('active')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        $this->table->appendCell('modified')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::getLongDateTime');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        $this->table->getForm()->appendField((new Select('active', ['' => '-- All --', 'y' => 'Active', 'n' => 'Inactive'])))
            ->setValue('y');

        $this->table->getForm()->appendField((new Select('type', Collection::listCombine(Company::TYPE_LIST)))
            ->prependOption('-- Type --', ''))
            ->setValue(Company::TYPE_CLIENT);


        // Add Table actions
        $this->table->appendAction(\Tk\Table\Action\Select::create('Active Status', 'fa fa-fw fa-times')
            ->setActions(['Active' => 'active', 'Disable' => 'disable'])
            ->setConfirmStr('Toggle active/disable on the selected rows?')
            ->addOnExecute(function(\Tk\Table\Action\Select $action) use ($rowSelect) {
                if (!isset($_POST[$action->getRequestKey()])) return;
                $active = trim(strtolower($_POST[$action->getRequestKey()] ?? 'active')) == 'active';
                $selected = $rowSelect->getSelected();
                foreach ($selected as $id) {
                    $obj = Company::find((int)$id);
                    $obj->active = $active;
                    $obj->save();
                }
            }));

        $this->table->appendAction(Csv::create()
            ->addOnExecute(function(Csv $action) {
                if (!$this->table->getCell(Company::getPrimaryProperty())) {
                    $this->table->prependCell(Company::getPrimaryProperty())->setHeader('id');
                }
                $filter = $this->table->getDbFilter()->resetLimits();
                return Company::findFiltered($filter);
            }));

        // execute table to init filter object
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $rows = Company::findFiltered($filter);
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
      <a href="/companyEdit" title="Create Company" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Company</a>
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