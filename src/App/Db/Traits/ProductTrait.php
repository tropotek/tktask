<?php
namespace App\Db\Traits;

use App\Db\ProductCategory;

trait ProductTrait
{
    private ?ProductCategory $_product = null;

    public function getProductCategory(): ?ProductCategory
    {
        if (!$this->_product) $this->_product = ProductCategory::find($this->productId);
        return $this->_product;
    }

}
