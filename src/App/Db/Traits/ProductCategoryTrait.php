<?php
namespace App\Db\Traits;

use App\Db\ProductCategory;

trait ProductCategoryTrait
{
    private ?ProductCategory $_productCategory = null;

    public function getProductCategory(): ?ProductCategory
    {
        if (!$this->_productCategory) $this->_productCategory = ProductCategory::find($this->productCategoryId);
        return $this->_productCategory;
    }

}
