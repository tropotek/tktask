<?php
namespace App\Component;

use App\Db\StatusLog;
use App\Db\User;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Db;

class StatusLogTable extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    protected Table   $table;
    protected Db\Model $model;


    public function __construct(Db\Model $model)
    {
        $this->model = $model;
    }

    public function doDefault(): string
    {
        if (!User::getAuthUser()->isStaff()) return '';

        // init table
        $this->table = new Table();
        $this->table->hideReset();
        $this->table->setOrderBy('-created');
        $this->table->setLimit(25);
        $this->table->addCss('tk-table-sm');

        $this->table->appendCell('message')
            ->addHeaderCss('max-width');

        $this->table->appendCell('name')
            ->setHeader('Status')
            ->addCss('text-nowrap text-center')
            ->setSortable(true);

        $this->table->appendCell('created')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\DateTime::onValue');

        // execute table
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $filter['model'] = $this->model;
        $rows = StatusLog::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());

        return $this->show()->toString();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $this->table->getRenderer()->setFooterEnabled(false);
        $template->appendTemplate('content', $this->table->show());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-cogs"></i> <span var="title">Status Log</span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}
