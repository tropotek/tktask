<?php
namespace App\Db;

use App\Db\Traits\ProductCategoryTrait;
use Bs\Registry;
use DateTime;
use Tk\Config;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Exception;
use Tk\Money;

class Product extends Model
{
    use ProductCategoryTrait;

    const int LABOR_CAT_ID           = 1;
    const int DEFAULT_LABOUR_PRODUCT = 1;

    const string CYCLE_EACH      = 'each';
    const string CYCLE_WEEK      = 'week';
    const string CYCLE_FORTNIGHT = 'fortnight';
    const string CYCLE_MONTH     = 'month';
    const string CYCLE_QUARTER   = 'quarter';
    const string CYCLE_YEAR      = 'year';
    const string CYCLE_BIANNUAL  = 'biannual';

    const array CYCLE_LIST = [
        self::CYCLE_EACH      => 'Each',
        self::CYCLE_WEEK      => 'Weekly',
        self::CYCLE_FORTNIGHT => 'Fortnightly',
        self::CYCLE_MONTH     => 'Monthly',
        self::CYCLE_QUARTER   => 'Quarterly',
        self::CYCLE_YEAR      => 'Yearly',
        self::CYCLE_BIANNUAL  => 'Biannually',
    ];

    public int        $productId         = 0;
    public int        $productCategoryId = 0;
    public string     $cycle             = self::CYCLE_EACH;
    public string     $name              = '';
    public string     $code              = '';
    public string     $description       = '';
    public string     $notes             = '';
    public bool       $active            = true;
    public Money      $price;
    public ?\DateTime $modified          = null;
    public ?\DateTime $created           = null;

    public string     $categoryName      = '';

    private ?ProductCategory $_productCategory = null;


    public function __construct()
    {
        $this->price    = Money::create();
        $this->code     = 'TK-'.(new \DateTime())->format('Y').'-0000-00';
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->productId) {
            $values['product_id'] = $this->productId;
            Db::update('product', 'product_id', $values);
        } else {
            unset($values['product_id']);
            Db::insert('product', $values);
            $this->productId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public static function getDefaultLaborProduct(): self
    {
        $id = (int)Registry::getValue('site.product.labor.default', self::DEFAULT_LABOUR_PRODUCT);
        $obj = self::find($id);
        if (!($obj instanceof Product)) throw new Exception("Failed to find product id {$id}");
        return $obj;
    }

    public static function findByCode(string $productCode): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM v_product
            WHERE code = :productCode",
            compact('productCode'),
            self::class
        );
    }

    /**
     * @return array<int,Product>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);
        $filter->appendFrom(static::getPrimaryTable() . ' a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.name) LIKE LOWER(:lSearch)';
            $w .= 'OR LOWER(a.code) LIKE LOWER(:lSearch)';
            $w .= 'OR a.product_id = :search';
            $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['productId'] = $filter['id'];
        }
        if (!empty($filter['productId'])) {
            if (!is_array($filter['productId'])) $filter['productId'] = [$filter['productId']];
            $filter->appendWhere('AND a.product_id IN :productId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.product_id NOT IN :exclude', $filter['exclude']);
        }

        if (!empty($filter['productCategoryId'])) {
            $filter->appendWhere('AND a.product_category_id = :productCategoryId');
        }
        if (!empty($filter['cycle'])) {
            $filter->appendWhere('AND a.cycle = :cycle');
        }
        if (!empty($filter['name'])) {
            $filter->appendWhere('AND a.name = :name');
        }
        if (!empty($filter['code'])) {
            $filter->appendWhere('AND a.code = :code');
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

        if (!$this->productCategoryId) {
            $errors['productCategoryId'] = 'Invalid value: productCategoryId';
        }

        if (!$this->name) {
            $errors['name'] = 'Invalid value: name';
        }

        if (!$this->code) {
            $errors['code'] = 'Invalid value: code';
        } else {
            $dup = self::findByCode($this->code);
            if ($dup && $dup->productId != $this->productId) {
                $errors['code'] = 'This product code already exists';
            }
        }

        return $errors;
    }

}