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
            $map->addDataType(new Db\Integer('exampleId', 'example_id'));
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

        // TODO: Refactor the form and table mapper out...
        //       - I think we should stick to adding field formatting to the
        //       fields and cells??? Need to re-think how this is done,
        //       - I feel all these extra mappers are a little cumbersome
        //       and do not add any real value???
        //       - Have a think about including views to present table data
        //       and what affect that would have on the mappers and queries
        //       - Maybe we need to refactor the findFiltered()/makeQuery()
        //       methods to accommodate view names?
        //
        //       Plenty of things to consider here so have a think about it

        if (!$this->getDataMappers()->has(self::DATA_MAP_FORM)) {
            $map = new DataMap();
            $map->addDataType(new Form\Text('exampleId'));
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
            $map->addDataType(new Form\Text('exampleId'));
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
                $w .= sprintf('a.example_id = %d OR ', $id);
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['exampleId'] = $filter['id'];
        }
        if (!empty($filter['exampleId'])) {
            $w = $this->makeMultiQuery($filter['exampleId'], 'a.example_id');
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
            $w = $this->makeMultiQuery($filter['exclude'], 'a.example_id', 'AND', '!=');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        return $filter;
    }

}
