<?php
namespace App\Db;

use Tk\Db;
use Tk\Db\Filter;
use Tk\Db\Model;

class TaskCategory extends Model
{

    public int        $taskCategoryId = 0;
    public string     $name           = '';
    public string     $description    = '';
    public int        $orderBy        = 0;
    public bool       $active         = true;
    public ?\DateTime $modified   = null;
    public ?\DateTime $created    = null;


    public function __construct()
    {
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->taskCategoryId) {
            $values['task_category_id'] = $this->taskCategoryId;
            Db::update('task_category', 'task_category_id', $values);
        } else {
            unset($values['task_category_id']);
            Db::insert('task_category', $values);
            $this->taskCategoryId = Db::getLastInsertId();
        }

        $this->reload();
    }

    /**
     * @return array<int,TaskCategory>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);
        $filter->appendFrom(static::getPrimaryTable() . ' a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.name) LIKE LOWER(:lSearch)';
            $w .= 'OR a.task_category_id = :search';
            $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['taskCategoryId'] = $filter['id'];
        }
        if (!empty($filter['taskCategoryId'])) {
            if (!is_array($filter['taskCategoryId'])) $filter['taskCategoryId'] = [$filter['taskCategoryId']];
            $filter->appendWhere('AND a.task_category_id IN :taskCategoryId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.task_category_id NOT IN :exclude', $filter['exclude']);
        }

        if (!empty($filter['name'])) {
            $filter->appendWhere('AND a.name = :name');
        }
        if (is_bool(truefalse($filter['active'] ?? null))) {
            $filter['active'] = truefalse($filter['active']);
            $filter->appendWhere('AND a.active = :active');
        }

        return Db::query("
            SELECT *
            FROM {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->name) {
            $errors['name'] = 'Invalid value: name';
        }

        return $errors;
    }

}