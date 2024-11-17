<?php
namespace App\Db;

use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;

class TaskCategory extends Model
{

    public int    $taskCategoryId = 0;
    public string $name           = '';
    public string $label          = '';
    public string $description    = '';
    public int    $orderBy        = 0;
    public bool   $active         = true;

    public \DateTimeImmutable $modified;
    public \DateTimeImmutable $created;


    public function __construct()
    {
        $this->modified = new \DateTimeImmutable();
        $this->created  = new \DateTimeImmutable();

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

    public static function find(int $taskCategoryId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM task_category
            WHERE task_category_id = :taskCategoryId",
            compact('taskCategoryId'),
            self::class
        );
    }

    /**
     * @return array<int,TaskCategory>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM task_category",
            [],
            self::class
        );
    }

    /**
     * @return array<int,TaskCategory>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.name) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.label) LIKE LOWER(:search) OR ';
            $w .= 'a.task_category_id = :search OR ';
            if (is_numeric($filter['search'])) {
                $w .= 'a.task_category_id = :search OR ';
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['taskCategoryId'] = $filter['id'];
        }
        if (!empty($filter['taskCategoryId'])) {
            if (!is_array($filter['taskCategoryId'])) $filter['taskCategoryId'] = [$filter['taskCategoryId']];
            $filter->appendWhere('a.task_category_id IN :taskCategoryId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.task_category_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = :name AND ');
        }
        if (!empty($filter['label'])) {
            $filter->appendWhere('a.label = :label AND ');
        }
        if (is_bool($filter['active'] ?? '')) {
            $filter->appendWhere('a.active = :active AND ');
        }

        return Db::query("
            SELECT *
            FROM task_category a
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->taskCategoryId) {
            $errors['taskCategoryId'] = 'Invalid value: taskCategoryId';
        }

        if (!$this->name) {
            $errors['name'] = 'Invalid value: name';
        }

        if (!$this->label) {
            $errors['label'] = 'Invalid value: label';
        }

        return $errors;
    }

}