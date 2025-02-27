<?php
namespace App\Db;

use App\Db\Traits\ProductCategoryTrait;
use Bs\Traits\TimestampTrait;
use DateTime;
use Tk\Config;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Exception;
use Tk\Money;

class Product extends Model
{
    use TimestampTrait;
    use ProductCategoryTrait;

    const int    LABOR_CAT_ID    = 1;
    const int    DEFAULT_LABOUR_PRODUCT = 1;

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

    public int     $productId         = 0;
    public int     $productCategoryId = 0;
    public string  $cycle             = self::CYCLE_EACH;
    public string  $name              = '';
    public string  $code              = '';
    public string  $description       = '';
    public string  $notes             = '';
    public bool    $active            = true;

    public Money    $price;
    public DateTime $modified;
    public DateTime $created;

    private ?ProductCategory $_category = null;


    public function __construct()
    {
        $this->modified = new DateTime();
        $this->created  = new DateTime();
        $this->price    = Money::create();
        $this->code     = 'TK-'.$this->getCreated('Y').'-0000-00';
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

    public static function getDefaultLaborProduct(): static
    {
        $config = Config::instance();
        $id = (int)$config->get('site.product.labor.default', self::DEFAULT_LABOUR_PRODUCT);
        $obj = self::find($id);
        if (!($obj instanceof Product)) throw new Exception("Failed to find product id {$id}");
        return $obj;
    }

    public static function find(int $productId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM product
            WHERE product_id = :productId",
            compact('productId'),
            self::class
        );
    }

    public static function findByCode(string $productCode): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM product
            WHERE code = :productCode",
            compact('productCode'),
            self::class
        );
    }

    /**
     * @return array<int,Product>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM product",
            [],
            self::class
        );
    }

    /**
     * @return array<int,Product>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.name) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.code) LIKE LOWER(:search) OR ';
            $w .= 'a.product_id = :search OR ';
            if (is_numeric($filter['search'])) {
                $w .= 'a.product_id = :search OR ';
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['productId'] = $filter['id'];
        }
        if (!empty($filter['productId'])) {
            if (!is_array($filter['productId'])) $filter['productId'] = [$filter['productId']];
            $filter->appendWhere('a.product_id IN :productId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.product_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['productCategoryId'])) {
            $filter->appendWhere('a.product_category_id = :productCategoryId AND ');
        }
        if (!empty($filter['cycle'])) {
            $filter->appendWhere('a.cycle = :cycle AND ');
        }
        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = :name AND ');
        }
        if (!empty($filter['code'])) {
            $filter->appendWhere('a.code = :code AND ');
        }
        if (is_bool(truefalse($filter['active'] ?? null))) {
            $filter['active'] = truefalse($filter['active']);
            $filter->appendWhere('a.active = :active AND ');
        }

        return Db::query("
            SELECT *
            FROM product a
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->productId) {
            $errors['productId'] = 'Invalid value: productId';
        }

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