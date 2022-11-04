<?php

namespace App\Controller\User;

use App\Db\UserMap;
use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Tk\Db\Tool;
use Tk\Table;
use Tk\TableRenderer;

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

        $this->table->appendCell(new Table\Cell\Text('type'));
        $this->table->appendCell(new Table\Cell\Text('username'));
        $this->table->appendCell(new Table\Cell\Text('nameFirst'));
        $this->table->appendCell(new Table\Cell\Text('nameLast'));
        $this->table->appendCell(new Table\Cell\Text('email'))->addOnShow(function (Table\Cell\Text $cell, Template $template) {
            $cell->getRow()->setAttr('data-row-id', $cell->getRow()->getId());
            $cell->setAttr('title', $cell->getValue());
            return $template;
        });
        $this->table->appendCell(new Table\Cell\Text('active'));
        $this->table->appendCell(new Table\Cell\Text('modified'));
        $this->table->appendCell(new Table\Cell\Text('created'));

        $this->table->addCss('table-hover');
        $this->table->getRow()->setAttr('data-test');

        // TODO: Setup Table Filters

        // TODO: Setup Table Actions

        $tool = Tool::create('created');
        $list = UserMap::create()->findFiltered([], $tool);
        $listData = [];
        foreach ($list as $obj) {
            $a = [];
            UserMap::create()->getTableMap()->loadArray($a, $obj);
            $listData[] = $a;
        }

        // TODO: Implement some sort of ListAdaptor or ListDecorator ????


        $this->table->setList($listData);

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