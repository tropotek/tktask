<?php
namespace App\Controller\Recurring;

use App\Db\Recurring;
use App\Db\Team;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Db;
use Tk\Form\Field\Input;
use Tk\Table\Action\ColumnSelect;
use Tk\Table\Action\Csv;
use Tk\Table\Action\Delete;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;
use Tk\Uri;

class Manager extends ControllerAdmin
{
    protected ?Table $table = null;

    public function doDefault(): void
    {
        Breadcrumbs::reset();
        $this->getPage()->setTitle('Recurring Manager', 'fas fa-money-bill-wave');
        $this->validateAccess(User::getAuthUser()?->isStaff() ?? false);

        // init table
        $this->table = new Table();
        $this->table->setOrderBy('company_id,next_on');
        $this->table->setLimit(25);

        $rowSelect = RowSelect::create('id', 'recurringId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('description')
            ->setSortable(true)
            ->addHeaderCss('max-width')
            ->addOnHtml(function(Recurring $obj, Cell $cell) {
                $url = Uri::create('/recurringEdit', ['recurringId' => $obj->recurringId]);
                return sprintf('<a href="%s">%s</a>', $url, $obj->description);
            });

        $this->table->appendCell('companyId')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue(function(Recurring $obj, Cell $cell) {
                return $obj->getCompany()->name;
            });

        $this->table->appendCell('billablePrice')
            ->setHeader('Billable')
            ->addHeaderCss('text-end')
            ->addCss('text-nowrap text-end')
            ->setSortable(true);

        $this->table->appendCell('cycle')
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap text-center')
            ->setSortable(true);

        $this->table->appendCell('nextOn')
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap text-center')
            ->setHeader('Next Invoice')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::getLongDate');

        $this->table->appendCell('active')
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        $this->table->appendCell('issue')
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        $this->table->appendCell('startOn')
            ->addHeaderCss('text-end')
            ->addCss('text-nowrap text-end')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::getLongDate');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');


        // Add Table actions
        $this->table->appendAction(ColumnSelect::create());
        $this->table->appendAction(Delete::createDefault(Recurring::class, $rowSelect));
        $this->table->appendAction(\Tk\Table\Action\Select::createActiveSelect(Recurring::class, $rowSelect));
        $this->table->appendAction(Csv::createDefault(Recurring::class, $rowSelect));

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
      <a href="/recurringEdit" title="Create Recurring" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Recurring</a>
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