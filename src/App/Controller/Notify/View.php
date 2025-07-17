<?php
namespace App\Controller\Notify;

use App\Db\Notify;
use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Db;
use Tk\Form\Field\Input;
use Tk\Table\Action\Delete;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;

class View extends ControllerAdmin
{
    protected ?Table $table = null;


    public function doDefault(): void
    {
        // Breadcrumbs::reset();
        $this->setUserAccess();
        $this->getPage()->setTitle('My Notifications', 'fe-bell noti-icon');


        // init table
        $this->table = new Table('notify-tbl');
        $this->table->setOrderBy('is_read,-created');
        $this->table->setLimit(25);

        $rowSelect = RowSelect::create('id', 'notifyId');
        $this->table->appendCell($rowSelect);

        $this->table->appendCell('title')
            ->addHeaderCss('max-width')
            ->addCss('text-nowrap')
            ->addOnValue(function(Notify $obj, Cell $cell) {
                if ($obj->isRead) {
                    $cell->getTable()->getRowAttrs()->addCss('is-read');
                }
                return sprintf('<a href="%s" class="notify-click" data-notify-id="%s">%s</a>',
                    $obj->url, $obj->notifyId, $obj->title);
            });

        $this->table->appendCell('message')
            ->addHeaderCss('max-width')
            ->addCss('text-small text-nowrap');

        $this->table->appendCell('read')
            ->addOnValue(function(Notify $obj, Cell $cell) {
                return $obj->isRead ? 'Yes' : 'No';
            });

        $this->table->appendCell('created')
            ->addCss('text-nowrap')
            ->addOnValue('\Tk\Table\Type\Date::getLongDateTime');

        /// ...

        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');


        // Add Table actions
        $this->table->appendAction(Delete::create()
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnDelete(function (Delete $action, array $selected) {
                foreach ($selected as $notify_id) {
                    Db::delete('notify', compact('notify_id'));
                }
            }));

        // execute table
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $filter->replace([
            'userId' => User::getAuthUser()->userId,
        ]);
        $rows = Notify::findFiltered($filter);
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
<style>
    #notify-tbl tbody .mTitle {
        font-weight: bold;
    }
    #notify-tbl tbody .is-read .mTitle {
        font-weight: normal;
    }
</style>
  <div class="card mb-3">
    <div class="card-header"><i var="icon"></i> <span var="title"></span></div>
    <div class="card-body" var="content"></div>
  </div>
</div>
HTML;
        return Template::load($html);
    }

}