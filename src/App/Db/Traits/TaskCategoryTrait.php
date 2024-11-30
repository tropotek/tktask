<?php
namespace App\Db\Traits;

use App\Db\TaskCategory;

trait TaskCategoryTrait
{
    private ?TaskCategory $_taskCategory = null;

    public function getTaskCategory(): ?TaskCategory
    {
        if (!$this->_taskCategory) $this->_taskCategory = TaskCategory::find($this->categoryId);
        return $this->_taskCategory;
    }

}
