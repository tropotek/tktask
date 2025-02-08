<?php
namespace App\Component;

use App\Db\StatusLog;
use App\Db\User;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Db;
use Tk\Log;

class StatusLogTable extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    protected Table   $table;
    protected ?Db\Model $model = null;


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $fid = (int)($_POST['fid'] ?? $_GET['fid'] ?? 0);
        $fkey = trim($_POST['fkey'] ?? $_GET['fkey'] ?? '');

        if (!class_exists($fkey)) {
            Log::error("failed to find model {$fkey}");
            return null;
        }

        $this->model = $fkey::findDbModel($fid);
        if (!$this->model) {
            Log::error("failed to find model {$fkey} with id {$fid}");
            return null;
        }

        // init table
        $this->table = new Table('status-log-tbl');
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

        return $this->show();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $this->table->getRenderer()->setFooterEnabled(false);
        $template->appendTemplate('content', Table::toHtmxTable($this->table));

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
