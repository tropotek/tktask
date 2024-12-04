<?php
namespace App\Controller\Project;

use App\Db\Company;
use App\Db\Project;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Alert;
use Tk\Collection;
use Tk\Form\Field\Input;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;
use Tk\Table\Action\Csv;
use Tk\Uri;
use Tk\Db;

class Manager extends ControllerAdmin
{
    protected ?Table $table = null;

    public function doDefault(): void
    {
        $this->getPage()->setTitle('Project Manager');

        $user = User::getAuthUser();
        if (!($user && $user->isStaff())) {
            Alert::addWarning('You do not have permission to access the requested page');
            $user?->getHomeUrl()->redirect();
            Uri::create('/')->redirect();
        }

        // init table
        $this->table = new Table();
        $this->table->setOrderBy('project_id');
        $this->table->setLimit(25);

        $rowSelect = RowSelect::create('id', 'projectId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('name')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addHeaderCss('max-width')
            ->addOnValue(function(\App\Db\Project $obj, Cell $cell) {
                $url = Uri::create('/projectEdit', ['projectId' => $obj->projectId]);
                return sprintf('<a href="%s">%s</a>', $url, $obj->name);
            });

        $this->table->appendCell('companyId')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue(function(\App\Db\Project $obj, Cell $cell) {
                return $obj->getCompany()?->name ?? 'N/A';
            });

        $this->table->appendCell('status')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue(function(\App\Db\Project $obj, Cell $cell) {
                return sprintf('<span class="badge text-bg-%s">%s</span>',
                    Project::STATUS_CSS[$obj->status],
                    Project::STATUS_LIST[$obj->status]
                );
            });

        $this->table->appendCell('quote')
            ->addCss('text-nowrap')
            ->setSortable(true);

        // TODO: these values should be sourced from the view to enable sorting???
        // Est. Cost
        // todo wait until Task exist
        $this->table->appendCell('estCost')
            ->addCss('text-nowrap');

        // Progress
        // todo wait until Task exist
        $this->table->appendCell('progress')
            ->addCss('text-nowrap');

        // Open Tasks
        // todo wait until Task exist
        $this->table->appendCell('openTasks')
            ->addCss('text-nowrap');

        $this->table->appendCell('userId')
            ->setHeader('Lead')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue(function(\App\Db\Project $obj, Cell $cell) {
                return $obj->getUser()?->nameShort ?? 'N/A';
            });

        $this->table->appendCell('dateStart')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\DateFmt::onValue');

        $this->table->appendCell('dateEnd')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\DateFmt::onValue');


        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        $this->table->getForm()->appendField((new \Tk\Form\Field\Select('status', Project::STATUS_LIST))
            ->setMultiple(true)
            ->setAttr('placeholder', '-- Status --')
            ->addCss('tk-checkselect'))
            ->setPersistent(true)
            ->setValue(['pending', 'active']);

        $cats = Company::findFiltered(Db\Filter::create(['type' => Company::TYPE_CLIENT], '-active, name'));
        $list = Collection::toSelectList($cats, 'companyId', fn($obj) => ($obj->active ? '' : '- ') . $obj->name);
        $this->table->getForm()->appendField((new \Tk\Form\Field\Select('companyId', $list))
            ->prependOption('-- Company --', ''));


        // Add Table actions
        $this->table->appendAction(Csv::create()
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnCsv(function(Csv $action, array $selected) {
                $action->setExcluded(['id', 'actions']);
                $filter = $this->table->getDbFilter();
                $this->table->getCell('name')->getOnValue()->reset();
                if ($selected) {
                    $rows = Project::findFiltered($filter);
                } else {
                    $rows = Project::findFiltered($filter->resetLimits());
                }
                return $rows;
            }));

        // execute table
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $rows = Project::findFiltered($filter);
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
      <a href="#" title="Create Project" class="btn btn-outline-secondary" var="create"><i class="fa fa-plus"></i> Create Project</a>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-project-diagram"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}