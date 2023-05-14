<?php
namespace App\Table;

use Bs\Db\FileMap;
use Bs\Db\Permission;
use Tk\Db\Map\ArrayObject;
use Tk\Db\Tool;
use Tk\Table\Action\ColumnSelect;
use Tk\Table\Action\Csv;
use Tk\Table\Cell;
use Tk\Table\Cell\Text;

/**
 * Example:
 * <code>
 *   $table = new File::create();
 *   $table->init();
 *   $list = ObjectMap::getObjectListing();
 *   $table->setList($list);
 *   $tableTemplate = $table->show();
 *   $template->appendTemplate($tableTemplate);
 * </code>
 *
 * @author Mick Mifsud
 * @created 2019-05-23
 * @link http://tropotek.com.au/
 * @license Copyright 2019 Tropotek
 */
class User extends \Bs\TableInterface
{

    /**
     * @return $this
     * @throws \Exception
     */
    public function init()
    {
        $this->addCss('tk-file-table');

        $this->appendCell(new Cell\Checkbox('id'));
        $this->appendCell(new Text('path'))->setLabel('Status')
            ->setLinkAttrs('target="_blank1"')
            ->addOnPropertyValue(function(Text $cell, \Bs\Db\File $obj, $value) {
                $value = '';
                if ($obj->getPath()) {
                    $value = basename($obj->getPath());
                    $cell->setUrl($obj->getUrl());
                }
                return $value;
            });
        $this->appendCell(new Text('userId'))->addOnPropertyValue(function(Text $cell, \Bs\Db\File $obj, $value) {
            $value = '';
            if ($obj->getUser())
                $value = $obj->getUser()->getName();
            return $value;
        });
        $this->appendCell(new Text('fkey'))->setLabel('Type');
        $this->appendCell(new Text('mime'));
        $this->appendCell(new Text('bytes'))->setLabel('Size');
        $this->appendCell(new Text('notes'));
        $this->appendCell(new Cell\Date('modified'));
        $this->appendCell(new Cell\Date('created'));

        // Filters
        //$this->appendFilter(new Field\Input('keywords'))->setAttr('placeholder', 'Search');

        // Actions
        $this->appendAction(\Tk\Table\Action\Delete::create());
        $this->appendAction(ColumnSelect::create()->setUnselected(['modified', 'notes', 'mime']));
        $this->appendAction(Csv::create());

        return $this;
    }

    /**
     * @param array $filter
     * @param null|Tool $tool
     * @return ArrayObject|StatusAlias[]
     * @throws \Exception
     */
    public function findList($filter = array(), $tool = null)
    {
        if (!$tool) $tool = $this->getTool('created DESC');
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = FileMap::create()->findFiltered($filter, $tool);
        return $list;
    }


}