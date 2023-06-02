<?php
namespace App\Db;

use Tk\DataMap\DataMap;
use Tk\Db\Mapper\Filter;
use Tk\Db\Mapper\Mapper;
use Tk\Db\Mapper\Result;
use Tk\Db\Tool;
use Tk\DataMap\Db;
use Tk\DataMap\Form;
use Tk\DataMap\Table;

class ExampleMap extends Mapper
{

    public function makeDataMaps(): void
    {

        if (!$this->getDataMappers()->has(self::DATA_MAP_DB)) {
            $map = new DataMap();
            $map->addDataType(new Db\Integer('id'));
            $map->addDataType(new Db\Text('name'));
            $map->addDataType(new Db\Text('nick'))->setNullable(true);
            $map->addDataType(new Db\Text('image'));
            $map->addDataType(new Db\Text('content'));
            $map->addDataType(new Db\Text('notes'));
            $map->addDataType(new Db\Boolean('active'));
            //$map->addDataType(new Db\Boolean('del'));
            $map->addDataType(new Db\Date('modified'));
            $map->addDataType(new Db\Date('created'));
//            $del = $map->addDataType(new Db\Boolean('del'));
//            $this->setDeleteType($del);
            $this->addDataMap(self::DATA_MAP_DB, $map);
        }

        if (!$this->getDataMappers()->has(self::DATA_MAP_FORM)) {
            $map = new DataMap();
            $map->addDataType(new Form\Text('id'));
            $map->addDataType(new Form\Text('name'));
            $map->addDataType(new Form\Text('nick'))->setNullable(true);
            //$map->addDataType(new Form\Text('image'));        // No need for file types to be mapped
            $map->addDataType(new Form\Text('content'));
            $map->addDataType(new Form\Text('notes'));
            $map->addDataType(new Form\Boolean('active'));
            $this->addDataMap(self::DATA_MAP_FORM, $map);
        }

        if (!$this->getDataMappers()->has(self::DATA_MAP_TABLE)) {
            $map = new DataMap();
            $map->addDataType(new Form\Text('id'));
            $map->addDataType(new Form\Text('name'));
            $map->addDataType(new Form\Text('nick'))->setNullable(true);
            $map->addDataType(new Form\Text('image'));
            $map->addDataType(new Form\Text('content'));
            $map->addDataType(new Form\Text('notes'));
            $map->addDataType(new Table\Boolean('active'));
            $map->addDataType(new Form\Date('modified'))->setDateFormat('d/m/Y h:i:s');
            $map->addDataType(new Form\Date('created'))->setDateFormat('d/m/Y h:i:s');
            $this->addDataMap(self::DATA_MAP_TABLE, $map);
        }
    }

    /**
     * @return Result|Example[]
     */
    public function findFiltered(array|Filter $filter, ?Tool $tool = null): Result
    {
        return $this->selectFromFilter($this->makeQuery(Filter::create($filter)), $tool);
    }

    public function makeQuery(Filter $filter): Filter
    {
        $filter->appendFrom('%s a ', $this->quoteParameter($this->getTable()));

        if (!empty($filter['search'])) {
            $kw = '%' . $this->getDb()->escapeString($filter['search']) . '%';
            $w = '';
            $w .= sprintf('a.name LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.nick LIKE %s OR ', $this->quote($kw));
            if (is_numeric($filter['search'])) {
                $id = (int)$filter['search'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $w = $this->makeMultiQuery($filter['id'], 'a.id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = %s AND ', $this->quote($filter['name']));
        }

        if (!empty($filter['nick'])) {
            $filter->appendWhere('a.nick = %s AND ', $this->quote($filter['nick']));
        }

        if (is_bool($filter['active'] ?? '')) {
            $filter->appendWhere('a.active = %s AND ', (int)$filter['active']);
        }

        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        return $filter;
    }

}
