<?php
namespace App\Db;

use Bs\Db\File;
use Bs\Db\Traits\TimestampTrait;
use Tt\Db;
use Tt\DbFilter;
use Tt\DbModel;

class Example extends DbModel
{
    use TimestampTrait;

    public int       $exampleId = 0;
    public string     $name     = '';
    public string     $image    = '';
    public bool       $active   = true;
    public ?\DateTime $modified = null;
    public ?\DateTime $created  = null;


    public function __construct()
    {
        $this->_TimestampTrait();
    }

    public function save(): void
    {
        $map = static::getDataMap();
        $values = $map->getArray($this);
        if ($this->exampleId) {
            $values['example_id'] = $this->exampleId;
            Db::update('example', 'example_id', $values);
        } else {
            unset($values['example_id']);
            Db::insert('example', $values);
            $this->exampleId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public function delete(): bool
    {
        return (false !== Db::delete('example', ['example_id' => $this->exampleId]));
    }

    public function getFileList(array $filter = []): array
    {
        $filter += ['model' => $this];
        return File::findFiltered($filter);
    }

    public function getDataPath(): string
    {
        return sprintf('/exampleFiles/%s', $this->exampleId);
    }

    public static function find(int $id): ?static
    {
        return Db::queryOne("
                SELECT *
                FROM example
                WHERE example_id = :id",
            compact('id'),
            self::class
        );
    }

    public static function findAll(): array
    {
        return Db::query(
            "SELECT * FROM example",
            null,
            self::class
        );
    }

    public static function findFiltered(array|DbFilter $filter): array
    {
        $filter = DbFilter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'name LIKE :search OR ';
            $w .= 'image LIKE :search OR ';
            $w .= 'example_id LIKE :search OR ';
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['exampleId'] = $filter['id'];
        }
        if (!empty($filter['exampleId'])) {
            $filter->appendWhere('(example_id IN :exampleId) AND ');
        }

        if (!empty($filter['name'])) {
            $filter->appendWhere('name = :name AND ');
        }

        if (isset($filter['active'])) {
            $filter['active'] = truefalse($filter['active']);
            $filter->appendWhere('active = :active AND ');
        }

        if (!empty($filter['exclude'])) {
            $filter->appendWhere('(example_id NOT IN :exclude) AND ');
        }

        return Db::query("
            SELECT *
            FROM example
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

    /**
     * Validate this object's current state and return an array
     * with error messages. This will be useful for validating
     * objects for use within forms.
     */
    public function validate(): array
    {
        $errors = [];

        if (!$this->name) {
            $errors['name'] = 'Invalid field value';
        }
        return $errors;
    }

}
