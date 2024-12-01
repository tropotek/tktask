<?php
namespace App\Db\Traits;

use App\Db\Product;

trait ProductTrait
{
    private ?Product $_product = null;

    public function getProduct(): ?Product
    {
        if (!$this->_product) $this->_product = Product::find($this->productId);
        return $this->_product;
    }

}
