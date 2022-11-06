<?php

namespace App\Controller\User;

use App\Db\UserMap;
use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Db\Tool;
use Tk\Table;
use Tk\TableRenderer;
use Tk\Uri;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class Manager extends PageController
{

    protected Table $table;


    public function __construct()
    {
        parent::__construct($this->getFactory()->getPublicPage());
        $this->getPage()->setTitle('User Manager');
    }

    public function doDefault(Request $request)
    {
        $this->table = new Table('newTable');

        $this->table->appendCell(new Table\Cell\Checkbox('id'));
        //$this->table->appendCell(new Table\Cell\Text('id'));
        $this->table->appendCell(new Table\Cell\Text('username'))->addOnShow(function (Table\Cell\Text $cell) {
            $obj = $cell->getRow()->getData();
            $cell->setUrl('/userEdit/'.$obj->getId());
//            $cell->getLink()->addCss('btn btn-sm btn-secondary col-auto');
//            $cell->addCss('text-center');
        });
        $this->table->appendCell(new Table\Cell\Text('nameFirst'));
        $this->table->appendCell(new Table\Cell\Text('nameLast'));
        $this->table->appendCell(new Table\Cell\Text('type'));
        $this->table->appendCell(new Table\Cell\Text('email'))->addOnShow(function (Table\Cell\Text $cell) {
            $cell->getRow()->setAttr('data-row-id', $cell->getRow()->getId());
            $cell->setAttr('title', $cell->getValue());
        });
        $this->table->appendCell(new Table\Cell\Text('active'));
        $this->table->appendCell(new Table\Cell\Text('modified'));
        $this->table->appendCell(new Table\Cell\Text('created'));

        $this->table->addCss('table-hover');
        $this->table->getRow()->setAttr('data-test');



        // TODO: Setup Table Filters




        // TODO: Setup Table Actions
        $this->table->appendAction(new Table\Action\Button('Test', 'fa fa-doc', Uri::create()));


        //$this->table->resetTableSession();
        $tool = $this->table->getTool('created DESC');

        // Query
        $list = UserMap::create()->findFiltered([], $tool);
        $this->table->setList($list, $tool->getFoundRows());

        $this->table->execute($request);


        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setText('title', $this->getPage()->getTitle());

        $renderer = new TableRenderer($this->table, $this->makePath($this->getConfig()->get('template.path.table')));
        $template->appendTemplate('content', $renderer->show());

        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
  <h2 var="title"></h2>
  <div var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }


}