<?php
namespace App\Db;

use Tk\DataMap\DataMap;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;

class ExpenseCategory extends Model
{
    public int        $expenseCategoryId = 0;
    public string     $name              = '';
    public string     $description       = '';
    /** percentage claimable for tax */
    public float      $claim             = 1;
    public bool       $active            = true;
    public ?\DateTime $modified          = null;
    public ?\DateTime $created           = null;


    public function __construct()
    {
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

    /**
     * @return array<int,ExpenseCategory>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);
        $filter->appendFrom(static::getPrimaryTable() . ' a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.name) LIKE LOWER(:lSearch)';
            $w .= 'OR LOWER(a.description) LIKE LOWER(:lSearch)';
            $w .= 'OR a.expense_category_id = :search';
            $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['expenseCategoryId'] = $filter['id'];
        }
        if (!empty($filter['expenseCategoryId'])) {
            if (!is_array($filter['expenseCategoryId'])) $filter['expenseCategoryId'] = [$filter['expenseCategoryId']];
            $filter->appendWhere('AND a.expense_category_id IN :expenseCategoryId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.expense_category_id NOT IN :exclude', $filter['exclude']);
        }

        if (!empty($filter['name'])) {
            $filter->appendWhere('AND a.name = :name');
        }
        if (!empty($filter['claim'])) {
            $filter->appendWhere('AND a.claim = :claim');
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