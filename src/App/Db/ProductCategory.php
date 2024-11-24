<?php
namespace App\Db;

use Bs\Traits\TimestampTrait;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;

class ProductCategory extends Model
{
    use TimestampTrait;

    public int     $productCategoryId = 0;
    public string  $name              = '';
    public string  $description       = '';
    public int     $orderBy           = 0;

    public \DateTime $modified;
    public \DateTime $created;



    public function __construct()
    {
        $this->modified = new \DateTime();
        $this->created = new \DateTime();
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->productCategoryId) {
            $values['product_category_id'] = $this->productCategoryId;
            Db::update('product_category', 'product_category_id', $values);
        } else {
            unset($values['product_category_id']);
            Db::insert('product_category', $values);
            $this->productCategoryId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public static function find(int $productCategoryId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM product_category
            WHERE product_category_id = :productCategoryId",
            compact('productCategoryId'),
            self::class
        );
    }

    /**
     * @return array<int,ProductCategory>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM product_category",
            [],
            self::class
        );
    }

    /**
     * @return array<int,ProductCategory>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.name) LIKE LOWER(:search) OR ';
            $w  = 'LOWER(a.description) LIKE LOWER(:search) OR ';
            $w .= 'a.product_category_id = :search OR ';
            if (is_numeric($filter['search'])) {
                $w .= 'a.product_category_id = :search OR ';
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['productCategoryId'] = $filter['id'];
        }
        if (!empty($filter['productCategoryId'])) {
            if (!is_array($filter['productCategoryId'])) $filter['productCategoryId'] = [$filter['productCategoryId']];
            $filter->appendWhere('a.product_category_id IN :productCategoryId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.product_category_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = :name AND ');
        }

        return Db::query("
            SELECT *
            FROM product_category a
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->productCategoryId) {
            $errors['productCategoryId'] = 'Invalid value: productCategoryId';
        }

        if (!$this->name) {
            $errors['name'] = 'Invalid value: name';
        }

        return $errors;
    }

}