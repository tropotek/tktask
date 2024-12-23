<?php
namespace App\Db;

use App\Db\Traits\InvoiceTrait;
use Bs\Traits\TimestampTrait;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Money;

class InvoiceItem extends Model
{
    use TimestampTrait;
    use InvoiceTrait;

    public int       $invoiceItemId = 0;
    public int       $invoiceId     = 0;
    public string    $productCode   = '';
    public string    $description   = '';
    public float     $qty           = 1;
    public Money     $price;
    public Money     $total;
    public string    $notes         = '';
    public \DateTime $modified;
    public \DateTime $created;

    protected ?Product $_product = null;
    protected ?Model   $_model   = null;


    public function __construct()
    {
        $this->price    = Money::create();
        $this->total    = Money::create();
        $this->modified = new \DateTime();
        $this->created  = new \DateTime();
    }

    public static function create(string $productCode, string $description, Money $price, float $qty = 1.0): static
    {
        $obj = new static();
        $obj->productCode = $productCode;
        $obj->description = $description;
        $obj->price       = $price;
        $obj->qty         = $qty;
        return $obj;
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->invoiceItemId) {
            $values['invoice_item_id'] = $this->invoiceItemId;
            Db::update('invoice_item', 'invoice_item_id', $values);
        } else {
            unset($values['invoice_item_id']);
            Db::insert('invoice_item', $values);
            $this->invoiceItemId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public function delete(): bool
    {
        return (false !== Db::delete('invoice_item', ['invoice_item_id' => $this->invoiceItemId]));
    }

    public function getProduct(): ?Product
    {
        if (!$this->_product) {
            $this->_product = Product::findByCode($this->productCode);
        }
        return $this->_product;
    }

    public function getModel(): ?Model
    {
        if (!$this->_model) {
            $code = $this->productCode;
            if (str_starts_with($code, 'TSK-')) {
                $id = intval(substr($code, 4));
                $this->_model = Task::find($id);
            } else {
                $this->_model = $this->getProduct();
            }
        }
        return $this->_model;
    }

//    public function getTotal(): Money
//    {
//        return $this->price->multiply($this->qty);
//    }

    public static function find(int $invoiceItemId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM v_invoice_item
            WHERE invoice_item_id = :invoiceItemId",
            compact('invoiceItemId'),
            self::class
        );
    }

    /**
     * @return array<int,InvoiceItem>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM v_invoice_item",
            [],
            self::class
        );
    }

    /**
     * @return array<int,InvoiceItem>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w = '';
            //$w .= 'LOWER(a.name) LIKE LOWER(:search) OR ';
            $w .= 'a.invoice_item_id = :search OR ';
            if (is_numeric($filter['search'])) {
                $w .= 'a.invoice_item_id = :search OR ';
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['invoiceItemId'] = $filter['id'];
        }
        if (!empty($filter['invoiceItemId'])) {
            if (!is_array($filter['invoiceItemId'])) $filter['invoiceItemId'] = [$filter['invoiceItemId']];
            $filter->appendWhere('a.invoice_item_id IN :invoiceItemId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.invoice_item_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['invoiceId'])) {
            $filter->appendWhere('a.invoice_id = :invoiceId AND ');
        }

        if (!empty($filter['productCode'])) {
            $filter->appendWhere('a.product_code = :productCode AND ');
        }

        return Db::query("
            SELECT *
            FROM v_invoice_item a
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->invoiceItemId) {
            $errors['invoiceItemId'] = 'Invalid value: invoiceItemId';
        }

        if (!$this->invoiceId) {
            $errors['invoiceId'] = 'Invalid value: invoiceId';
        }

        if (!$this->productCode) {
            $errors['productCode'] = 'Invalid value: productCode';
        }

        if (!$this->description) {
            $errors['description'] = 'Invalid value: description';
        }

        if ($this->qty == 0) {
            $errors['qty'] = 'Invalid value: qty';
        }

//        if ($this->price->getAmount() == 0) {
//            $errors['price'] = 'Invalid value unit price';
//        }

        return $errors;
    }

}