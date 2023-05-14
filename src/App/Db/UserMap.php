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

class UserMap extends Mapper
{

    public function makeDataMaps(): void
    {
        if (!$this->getDataMappers()->has(self::DATA_MAP_DB)) {
            $map = new DataMap();
            $map->addDataType(new Db\Integer('userId', 'user_id'));
            $map->addDataType(new Db\Text('uid'));
            $map->addDataType(new Db\Text('type'));
            $map->addDataType(new Db\Integer('permissions'));
            $map->addDataType(new Db\Text('username'));
            $map->addDataType(new Db\Text('password'));
            $map->addDataType(new Db\Text('email'));
            $map->addDataType(new Db\Text('name'));
            $map->addDataType(new Db\Text('timezone'));
            $map->addDataType(new Db\Boolean('active'));
            $map->addDataType(new Db\Text('hash'));
            $map->addDataType(new Db\Date('lastLogin', 'last_login'));
            $map->addDataType(new Db\Boolean('del'));
            $map->addDataType(new Db\Date('modified'));
            $map->addDataType(new Db\Date('created'));
            $del = $map->addDataType(new Db\Boolean('del'));
            $this->setDeleteType($del);
            $this->addDataMap(self::DATA_MAP_DB, $map);
        }

        if (!$this->getDataMappers()->has(self::DATA_MAP_FORM)) {
            $map = new DataMap();
            $map->addDataType(new Form\Text('userId'));
            $map->addDataType(new Form\Text('uid'));
            $map->addDataType(new Form\Text('type'));
            $map->addDataType(new Form\Integer('permissions'));
            $map->addDataType(new Form\Text('username'));
            $map->addDataType(new Form\Text('password'));
            $map->addDataType(new Form\Text('email'));
            $map->addDataType(new Form\Text('name'));
            $map->addDataType(new Form\Text('timezone'));
            $map->addDataType(new Form\Boolean('active'));
            $this->addDataMap(self::DATA_MAP_FORM, $map);
        }

        if (!$this->getDataMappers()->has(self::DATA_MAP_TABLE)) {
            $map = new DataMap();
            $map->addDataType(new Form\Text('userId'));
            $map->addDataType(new Form\Text('uid'));
            $map->addDataType(new Form\Text('type'));
            $map->addDataType(new Form\Integer('permissions'));
            $map->addDataType(new Form\Text('username'));
            $map->addDataType(new Form\Text('password'));
            $map->addDataType(new Form\Text('email'));
            $map->addDataType(new Form\Text('name'));
            $map->addDataType(new Form\Text('timezone'));
            $map->addDataType(new Table\Boolean('active'));
            $map->addDataType(new Form\Date('modified'))->setDateFormat('d/m/Y h:i:s');
            $map->addDataType(new Form\Date('created'))->setDateFormat('d/m/Y h:i:s');
            $this->addDataMap(self::DATA_MAP_TABLE, $map);
        }
    }

    public function findByUsername(string $username): ?User
    {
        return $this->findFiltered(array('username' => $username))->current();
    }

    /**
     * @return Result|User[]
     */
    public function findFiltered(array|Filter $filter, ?Tool $tool = null): Result
    {
        return $this->selectFromFilter($this->makeQuery(Filter::create($filter)), $tool);
    }

    public function makeQuery(Filter $filter): Filter
    {
        $filter->appendFrom('%s a', $this->quoteParameter($this->getTable()));

        if (!empty($filter['search'])) {
            $kw = '%' . $this->getDb()->escapeString($filter['search']) . '%';
            $w = '';
            $w .= sprintf('a.uid LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.name LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.username LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.email LIKE %s OR ', $this->quote($kw));
            if (is_numeric($filter['search'])) {
                $id = (int)$filter['search'];
                $w .= sprintf('a.user_id = %d OR ', $id);
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['userId'] = $filter['id'];
        }
        if (!empty($filter['userId'])) {
            $w = $this->makeMultiQuery($filter['userId'], 'a.user_id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['uid'])) {
            $filter->appendWhere('a.uid = %s AND ', $this->getDb()->quote($filter['uid']));
        }

        if (!empty($filter['type'])) {
            $w = $this->makeMultiQuery($filter['type'], 'a.type');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['username'])) {
            $filter->appendWhere('a.username = %s AND ', $this->getDb()->quote($filter['username']));
        }

        if (!empty($filter['email'])) {
            $filter->appendWhere('a.email = %s AND ', $this->quote($filter['email']));
        }

        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        return $filter;
    }

}
