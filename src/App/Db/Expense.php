<?php
namespace App\Db;

use App\Db\Traits\CompanyTrait;
use App\Db\Traits\ExpenseCategoryTrait;
use Bs\Traits\TimestampTrait;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Money;

class Expense extends Model
{
    use TimestampTrait;
    use ExpenseCategoryTrait;
    use CompanyTrait;

    public int        $expenseId   = 0;
    public int        $expenseCategoryId  = 0;
    public int        $companyId   = 0;
    public string     $invoiceNo   = '';
    public string     $receiptNo   = '';
    public string     $description = '';
    public \DateTime  $purchasedOn;
    public Money      $total;
    public ?\DateTime $modified    = null;
    public ?\DateTime $created     = null;


    public function __construct()
    {
        $this->purchasedOn = new \DateTime();
        $this->total = Money::create();
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->expenseId) {
            $values['expense_id'] = $this->expenseId;
            Db::update('expense', 'expense_id', $values);
        } else {
            unset($values['expense_id']);
            Db::insert('expense', $values);
            $this->expenseId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public function getDataPath(): string
    {
        return sprintf('/expense/%s', $this->expenseId);
    }

    /**
     * @return array<int,File>
     */
    public function getFiles(): array
    {
        return File::findFiltered(Filter::create(['model' => $this], '-created'));
    }

    function getRatio(): int
    {
        $type = $this->getExpenseCategory();
        if ($type) return $type->ratio;
        return 0;
    }

    /**
     * Get the amount chargeable to the business
     */
    function getBusinessTotal(): Money
    {
        return $this->total->multiply($this->getRatio());
    }


    public static function find(int $expenseId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM expense
            WHERE expense_id = :expenseId",
            compact('expenseId'),
            self::class
        );
    }

    /**
     * @return array<int,Expense>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM expense",
            [],
            self::class
        );
    }

    /**
     * @return array<int,Expense>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w = '';
            //$w .= 'LOWER(a.name) LIKE LOWER(:search) OR ';
            $w .= 'a.expense_id = :search OR ';
            if (is_numeric($filter['search'])) {
                $w .= 'a.expense_id = :search OR ';
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['expenseId'] = $filter['id'];
        }
        if (!empty($filter['expenseId'])) {
            if (!is_array($filter['expenseId'])) $filter['expenseId'] = [$filter['expenseId']];
            $filter->appendWhere('a.expense_id IN :expenseId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.expense_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['expenseCategoryId'])) {
            $filter->appendWhere('a.expense_category_id = :expenseCategoryId AND ');
        }

        if (!empty($filter['companyId'])) {
            $filter->appendWhere('a.company_id = :companyId AND ');
        }

        if (!empty($filter['invoiceNo'])) {
            $filter->appendWhere('a.invoice_no = :invoiceNo AND ');
        }

        if (!empty($filter['receiptNo'])) {
            $filter->appendWhere('a.receipt_no = :receiptNo AND ');
        }

        if (!empty($filter['dateStart'])) {
            if (($filter['dateStart'] instanceof \DateTime)) $filter['dateStart'] = $filter['dateStart']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('a.purchased_on >= :dateStart AND ');
        }
        if (!empty($filter['dateEnd'])) {
            if (($filter['dateEnd'] instanceof \DateTime)) $filter['dateEnd'] = $filter['dateEnd']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('a.purchased_on <= :dateEnd AND ');
        }

        return Db::query("
            SELECT *
            FROM expense a
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->expenseId) {
            $errors['expenseId'] = 'Invalid value: expenseId';
        }

        if (!$this->expenseCategoryId) {
            $errors['expenseCategoryId'] = 'Invalid value: expenseCategoryId';
        }

        if (!$this->companyId) {
            $errors['companyId'] = 'Invalid value: companyId';
        }

        if (!$this->description) {
            $errors['description'] = 'Invalid value: description';
        }

        if (!$this->invoiceNo && !$this->receiptNo) {
            $errors['invoiceNo'] = 'Invalid value: invoiceNo';
            $errors['receiptNo'] = 'Invalid value: receiptNo';
        }

        if (!$this->total->getAmount()) {
            $errors['total'] = 'Invalid value: total';
        }

        return $errors;
    }

}