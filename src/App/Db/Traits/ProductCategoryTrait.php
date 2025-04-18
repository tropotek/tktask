<?php
namespace App\Db\Traits;

use App\Db\ProductCategory;

trait ProductCategoryTrait
{
    private ?ProductCategory $_productCategory = null;

    public function getProductCategory(): ?ProductCategory
    {
        if (is_null($this->_productCategory)) {
            $this->_productCategory = ProductCategory::find($this->productCategoryId);
        }
        return $this->_productCategory;
    }

}
