<?php
namespace App\Db;

use Tk\DataMap\DataMap;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;

class ExpenseCategory extends Model
{
    public int       $expenseCategoryId = 0;
    public string    $name              = '';
    public string    $description       = '';
    /** amount claimable for tax */
    public float     $claim             = 1;
    public bool      $active            = true;
    public \DateTime $modified;
    public \DateTime $created;


    public function __construct()
    {
        $this->modified = new \DateTime();
        $this->created = new \DateTime();
    }

    public static function getFormMap(): DataMap
    {
        $map = parent::getFormMap();
        $map->addType(DataMap::makeFormType('percent', 'claim'));
        return $map;
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->expenseCategoryId) {
            $values['expense_category_id'] = $this->expenseCategoryId;
            Db::update('expense_category', 'expense_category_id', $values);
        } else {
            unset($values['expense_category_id']);
            Db::insert('expense_category', $values);
            $this->expenseCategoryId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public static function find(int $expenseCategoryId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM expense_category
            WHERE expense_category_id = :expenseCategoryId",
            compact('expenseCategoryId'),
            self::class
        );
    }

    /**
     * @return array<int,ExpenseCategory>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM expense_category",
            [],
            self::class
        );
    }

    /**
     * @return array<int,ExpenseCategory>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.name) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.description) LIKE LOWER(:search) OR ';
            $w .= 'a.expense_category_id = :search OR ';
            if (is_numeric($filter['search'])) {
                $w .= 'a.expense_category_id = :search OR ';
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['expenseCategoryId'] = $filter['id'];
        }
        if (!empty($filter['expenseCategoryId'])) {
            if (!is_array($filter['expenseCategoryId'])) $filter['expenseCategoryId'] = [$filter['expenseCategoryId']];
            $filter->appendWhere('a.expense_category_id IN :expenseCategoryId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.expense_category_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = :name AND ');
        }
        if (!empty($filter['claim'])) {
            $filter->appendWhere('a.claim = :claim AND ');
        }
        if (is_bool(truefalse($filter['active'] ?? null))) {
            $filter['active'] = truefalse($filter['active']);
            $filter->appendWhere('a.active = :active AND ');
        }

        return Db::query("
            SELECT *
            FROM expense_category a
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->expenseCategoryId) {
            $errors['expenseCategoryId'] = 'Invalid value: expenseCategoryId';
        }

        if (!$this->name) {
            $errors['name'] = 'Invalid value: name';
        }

        if (!$this->claim) {
            $errors['claim'] = 'Invalid value: claim';
        }

        return $errors;
    }

}