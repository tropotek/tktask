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

        // Map data from DB col types to type data for objects
        if (!$this->getDataMappers()->has(self::DATA_MAP_DB)) {
            $map = new DataMap();
            $map->addDataType(new Db\Integer('exampleId', 'example_id'));
            $map->addDataType(new Db\Text('name'));
            $map->addDataType(new Db\Text('nick'));
            $map->addDataType(new Db\Text('image'));
            $map->addDataType(new Db\Text('content'));
            $map->addDataType(new Db\Text('notes'));
            $map->addDataType(new Db\Boolean('active'));
            $map->addDataType(new Db\Date('modified'));
            $map->addDataType(new Db\Date('created'));
            // Use del col field for rows that should not be deleted physically
//            $map->addDataType(new Db\Boolean('del'));
//            $del = $map->addDataType(new Db\Boolean('del'));
//            $this->setDeleteType($del);
            $this->addDataMap(self::DATA_MAP_DB, $map);
        }

        // Map string data from forms to type data for objects
        if (!$this->getDataMappers()->has(self::DATA_MAP_FORM)) {
            $map = new DataMap();
            $map->addDataType(new Form\Text('exampleId'));
            $map->addDataType(new Form\Text('name'));
            $map->addDataType(new Form\Text('nick'));
            //$map->addDataType(new Form\Text('image'));        // No need for file types to be mapped
            $map->addDataType(new Form\Text('content'));
            $map->addDataType(new Form\Text('notes'));
            $map->addDataType(new Form\Boolean('active'));
            $this->addDataMap(self::DATA_MAP_FORM, $map);
        }
    }

    /**
     * @return Result|Example[]
     */
    public function findFiltered(array|Filter $filter, ?Tool $tool = null): Result
    {
        return $this->prepareFromFilter($this->makeQuery(Filter::create($filter)), $tool);
    }

    public function makeQuery(Filter $filter): Filter
    {
        $filter->appendFrom('%s a ', $this->quoteParameter($this->getTable()));

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $this->getDb()->escapeString($filter['search']) . '%';
            $w  = 'a.name LIKE :search OR ';
            $w .= 'a.nick LIKE :search OR ';
            $w .= 'a.example_id LIKE :search OR ';
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['exampleId'] = $filter['id'];
        }
        if (!empty($filter['exampleId'])) {
            $filter->appendWhere('(a.example_id IN (:exampleId)) AND ');
        }

        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = :name AND ');
        }

        if (!empty($filter['nick'])) {
            $filter->appendWhere('a.nick = :nick AND ');
        }

        if (!$this->isEmpty($filter['active'])) {
            $filter->appendWhere('a.active = :active AND ');
        }

        if (!empty($filter['exclude'])) {
            $filter->appendWhere('(a.example_id NOT IN (:exclude)) AND ');
        }
        return $filter;
    }

}
