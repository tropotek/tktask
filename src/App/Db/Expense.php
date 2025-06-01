<?php
namespace App\Db;

use App\Db\Traits\CompanyTrait;
use App\Db\Traits\ExpenseCategoryTrait;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Money;

class Expense extends Model
{
    use ExpenseCategoryTrait;
    use CompanyTrait;

    public int        $expenseId   = 0;
    public int        $expenseCategoryId  = 0;
    public int        $companyId   = 0;
    public string     $invoiceNo   = '';
    // public string     $receiptNo   = '';
    public string     $description = '';
    public \DateTime  $purchasedOn;
    public Money      $total;
    public string     $dataPath    = '';
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
        return $this->dataPath;
    }

    /**
     * @return array<int,File>
     */
    public function getFiles(): array
    {
        return File::findFiltered(Filter::create(['model' => $this], '-created'));
    }

    function getClaimRatio(): float
    {
        $type = $this->getExpenseCategory();
        if ($type) return $type->claim;
        return 0;
    }

    /**
     * Get the amount claimable for tax
     */
    function getClaimableAmount(): Money
    {
        return $this->total->multiply($this->getClaimRatio());
    }


    public static function find(int $expenseId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM v_expense
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
            FROM v_expense",
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
        $filter->appendFrom('v_expense a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . strtolower($filter['search']) . '%';
            $w  = "a.expense_id = :search ";
            $w .= "OR LOWER(CONCAT_WS(' ', a.description, a.invoice_no)) LIKE :lSearch ";
            if ($w) $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['expenseId'] = $filter['id'];
        }
        if (!empty($filter['expenseId'])) {
            if (!is_array($filter['expenseId'])) $filter['expenseId'] = [$filter['expenseId']];
            $filter->appendWhere('AND a.expense_id IN :expenseId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.expense_id NOT IN :exclude', $filter['exclude']);
        }

        if (!empty($filter['expenseCategoryId'])) {
            $filter->appendWhere('AND a.expense_category_id = :expenseCategoryId');
        }

        if (!empty($filter['companyId'])) {
            $filter->appendWhere('AND a.company_id = :companyId');
        }

        if (!empty($filter['invoiceNo'])) {
            $filter->appendWhere('AND a.invoice_no = :invoiceNo');
        }

//        if (!empty($filter['receiptNo'])) {
//            $filter->appendWhere('AND a.receipt_no = :receiptNo');
//        }

        if (!empty($filter['dateStart'])) {
            if (($filter['dateStart'] instanceof \DateTime)) $filter['dateStart'] = $filter['dateStart']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('AND a.purchased_on >= :dateStart');
        }
        if (!empty($filter['dateEnd'])) {
            if (($filter['dateEnd'] instanceof \DateTime)) $filter['dateEnd'] = $filter['dateEnd']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('AND a.purchased_on <= :dateEnd');
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

        if (!$this->companyId) {
            $errors['companyId'] = 'Invalid value: companyId';
        }

        if (!$this->description) {
            $errors['description'] = 'Invalid value: description';
        }

        if (!$this->invoiceNo) {
            $errors['invoiceNo'] = 'Invalid value: invoiceNo';
        }

//        if (!$this->invoiceNo && !$this->receiptNo) {
//            $errors['invoiceNo'] = 'Invalid value: invoiceNo';
//            $errors['receiptNo'] = 'Invalid value: receiptNo';
//        }

        if (!$this->total->getAmount()) {
            $errors['total'] = 'Invalid value: total';
        }

        return $errors;
    }

}