<?php
namespace App\Db\Traits;

use App\Db\ExpenseCategory;

trait ExpenseCategoryTrait
{
    private ?ExpenseCategory $_expenseCategory = null;

    public function getExpenseCategory(): ?ExpenseCategory
    {
        if (!$this->_expenseCategory) $this->_expenseCategory = ExpenseCategory::find($this->expenseCategoryId);
        return $this->_expenseCategory;
    }

}
