<?php
namespace App\Db;

use Bs\Traits\TimestampTrait;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Money;

class Product extends Model
{
    use TimestampTrait;

    // TODO: this may go into the recurring billing object not here
    //const string TYPE_EACH         = null;
    const string RECURRING_WEEK         = 'week';
    const string RECURRING_FORTNIGHT    = 'fortnight';
    const string RECURRING_MONTH        = 'month';
    const string RECURRING_QUARTER      = 'quarter';
    const string RECURRING_YEAR         = 'year';
    const string RECURRING_BIANNUAL     = 'biannual';

    const array RECURRING_LIST = [
        self::RECURRING_WEEK      => 'Weekly',
        self::RECURRING_FORTNIGHT => 'Fortnightly',
        self::RECURRING_MONTH     => 'Monthly',
        self::RECURRING_QUARTER   => 'Quarterly',
        self::RECURRING_YEAR      => 'Yearly',
        self::RECURRING_BIANNUAL  => 'Biannually',
    ];

    public int     $productId   = 0;
    public int     $categoryId  = 0;
    public ?string $recur       = null;
    public string  $name        = '';
    public string  $code        = '';
    public string  $description = '';
    public string  $notes       = '';
    public int     $orderBy     = 0;
    public bool    $active      = true;

    public Money     $price;
    public \DateTime $modified;
    public \DateTime $created;


    public function __construct()
    {
        $this->modified = new \DateTime();
        $this->created  = new \DateTime();
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

        if (!empty($filter['categoryId'])) {
            $filter->appendWhere('a.category_id = :categoryId AND ');
        }
        if (!empty($filter['recur'])) {
            $filter->appendWhere('a.recur = :recur AND ');
        }
        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = :name AND ');
        }
        if (!empty($filter['code'])) {
            $filter->appendWhere('a.code = :code AND ');
        }
        if (is_bool($filter['active'] ?? '')) {
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

        if (!$this->categoryId) {
            $errors['categoryId'] = 'Invalid value: categoryId';
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