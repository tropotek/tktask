<?php
namespace App\Component;

use App\Db\Domain;
use App\Db\DomainPing;
use App\Db\File;
use App\Db\User;
use Bs\Mvc\ComponentInterface;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Db;
use Tk\FileUtil;
use Tk\Log;
use Tk\Table\Cell;

class PingTable extends \Dom\Renderer\Renderer implements ComponentInterface
{
    protected Table    $table;
    protected ?Domain  $domain = null;


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $domainId = (int)($_REQUEST['domainId'] ?? 0);
        $this->domain = Domain::find($domainId);

        if (!($this->domain instanceof Domain)) {
            Log::error("invalid domain id {$domainId}");
            return null;
        }

        // init table
        $this->table = new Table('pings-tbl');
        $this->table->hideReset();
        $this->table->setOrderBy('-created');
        $this->table->setLimit(10);
        $this->table->addCss('tk-table-sm');

        $this->table->appendCell('status')
            ->setSortable(true)
            ->addHeaderCss('text-center')
            ->addCss('text-center')
            ->addOnValue(function(DomainPing $obj, Cell $cell) {
                if ($obj->status) {
                    return '<span class="badge bg-success">Online</span>';
                } else {
                    return '<span class="badge bg-danger">Offline</span>';
                }
            });

        $this->table->appendCell('siteName');
        $this->table->appendCell('timezone');
        $this->table->appendCell('bytes')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(DomainPing $obj, Cell $cell) {
                return FileUtil::bytes2String($obj->bytes ?? 0);
            });

        $this->table->appendCell('created')
            ->addHeaderCss('text-end')
            ->addCss('text-nowrap text-end')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::getLongDateTime');

        // execute table
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $filter['domainId'] = $domainId;
        $rows = DomainPing::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());

        return $this->show();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        //$this->table->getRenderer()->setFooterEnabled(false);
        $this->table->getRenderer()->setMaxPages(5);
        $template->appendTemplate('content', $this->table->htmxShow());

        return $template;
    }


    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
  <div class="card mb-3">
    <div class="card-header"><i class="ri-radar-line "></i> <span var="title">Pings</span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}
