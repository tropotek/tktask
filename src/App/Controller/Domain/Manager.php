<?php
namespace App\Controller\Domain;

use App\Db\Company;
use App\Db\Domain;
use App\Db\DomainPing;
use App\Db\Team;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Alert;
use Tk\Collection;
use Tk\Date;
use Tk\FileUtil;
use Tk\Form\Field\Input;
use Tk\Table\Action\ColumnSelect;
use Tk\Table\Action\Csv;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;
use Tk\Table\Action\Delete;
use Tk\Table\Action\Select;
use Tk\Uri;
use Tk\Db;

class Manager extends ControllerAdmin
{
    protected ?Table $table = null;

    public function doDefault(): void
    {
        $this->setUserAccess(User::PERM_SYSADMIN);
        $this->getPage()->setTitle('Domain Manager', 'fa fa-cogs');

        $action = trim($_REQUEST['action'] ?? '');

        if ($action == 'p') {
            Domain::pingAllDomains();
            Uri::create()->remove('action')->redirect();
        }

        // This will do for now, no need for warnings on every page
        $pings = Domain::findFiltered(['status' => false, 'active' => true]);
        if (count($pings)) {
            $msg = '<strong>Sites Offline:</strong><br>';
            foreach ($pings as $ping) {
                $msg .= sprintf('%s - %s<br>', $ping->companyName, $ping->url);
            }
            Alert::addWarning($msg);
        }

        // init table
        $this->table = new Table('domain');
        $this->table->setOrderBy('domain_id');
        $this->table->setLimit(25);

        $rowSelect = RowSelect::create('id', 'domainId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('action')
            ->setSortable(true)
            ->addCss('text-center')
            ->addOnHtml(function(Domain $obj, Cell $cell) {
                $url = Uri::create($obj->url);
                $url = $url->withPath('/');
                return <<<HTML
                    <a href="$url" class="btn btn-outline-primary" target="_blank" title="View Site"><span class="fas fa-globe"></span></a>
                HTML;
            });

        $this->table->appendCell('status')
            ->setSortable(true)
            ->addCss('text-center')
            ->addOnHtml(function(Domain $obj, Cell $cell) {
                if ($obj->status) {
                    return '<span class="badge bg-success">Online</span>';
                } else {
                    return '<span class="badge bg-danger">Offline</span>';
                }
            });

        $this->table->appendCell('uptime')
            ->addCss('text-nowrap')
            ->addHeaderCss('text-start')
            ->addOnHtml(function(Domain $obj, Cell $cell) {
                $pings = DomainPing::findFiltered(Db\Filter::create(['domainId' => $obj->domainId], '-created', 25));
                $values = [];
                $labels = [];
                foreach ($pings as $ping) {
                    $values[] = $ping->status ? 100 : -100;
                    $labels[] = $ping->created->format(Date::FORMAT_LONG_DATE);
                }
                $values = array_reverse($values);
                $labels = array_reverse($labels);
                $valuesJson = eattr(json_encode($values));
                $labelsJson = eattr(json_encode($labels));
                return '<span class="ping-spark" data-labels="'.$labelsJson.'" data-values="'.$valuesJson.'"></span>';
            });

        $this->table->appendCell('url')
            ->addCss('full-width')
            ->addHeaderCss('text-start')
            ->setSortable(true)
            ->addOnHtml(function(Domain $obj, Cell $cell) {
                $edit = Uri::create('/domainEdit')->set('domainId', $obj->domainId);
                return <<<HTML
                    <a href="$edit" title="Edit">{$obj->url}</a>
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

        $this->table->appendCell('active')
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        $this->table->appendCell('pingedAt')
            ->setHeader('Last Online')
            ->addHeaderCss('text-end')
            ->addCss('text-nowrap text-end')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::getLongDateTime');


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
        $this->table->appendAction(ColumnSelect::create());
        $this->table->appendAction(Delete::createDefault(Domain::class, $rowSelect));
        $this->table->appendAction(\Tk\Table\Action\Select::createActiveSelect(Domain::class, $rowSelect));
        $this->table->appendAction(Csv::createDefault(Domain::class, $rowSelect));


        // execute table
        $this->table->execute();

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
      <a href="/domainEdit" title="Create Domain" class="btn btn-outline-secondary"><i class="fa fa-plus"></i> Create Domain</a>
      <a href="/domainManager?action=p" title="Ping Domains" class="btn btn-outline-secondary" data-confirm="This action may take some time, continue?"><i class="fa fa-crosshairs"></i> Ping Domains</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i var="icon"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
  <script>
      jQuery(document).ready(function($) {
          $('.ping-spark').each(function() {
              let labels = $(this).data('labels');
              $(this).sparkline($(this).data('values'), {
                  type: 'tristate',
                  colorMap: $.range_map({
                      '-100': '#F1556C',
                      '100': '#1ABC9C'
                  }),
                  tooltipFormat: '{{value:levels}}',
                  tooltipFormatter: function(sparkline, options, fields) {
                    let str = (fields.value > 0 ? 'Online' : 'Offline') + '<br>' + labels[fields.offset];
                    return '<span style="color: #333333;">' + str + '</span>';
                  },
              });
          });
      });
  </script>
</div>
HTML;
        return Template::load($html);
    }

}