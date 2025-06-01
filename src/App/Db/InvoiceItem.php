<?php
namespace App\Db;

use App\Db\Traits\InvoiceTrait;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Money;

class InvoiceItem extends Model
{
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

    public static function create(string $productCode, string $description, Money $price, float $qty = 1.0): self
    {
        $obj = new self();
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
        $filter->appendFrom('v_invoice_item a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.product_code) LIKE LOWER(:lSearch)';
            $w .= 'OR a.invoice_item_id = :search';
            $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['invoiceItemId'] = $filter['id'];
        }
        if (!empty($filter['invoiceItemId'])) {
            if (!is_array($filter['invoiceItemId'])) $filter['invoiceItemId'] = [$filter['invoiceItemId']];
            $filter->appendWhere('AND a.invoice_item_id IN :invoiceItemId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.invoice_item_id NOT IN :exclude', $filter['exclude']);
        }

        if (!empty($filter['invoiceId'])) {
            $filter->appendWhere('AND a.invoice_id = :invoiceId');
        }

        if (!empty($filter['productCode'])) {
            $filter->appendWhere('AND a.product_code = :productCode');
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

        if (!$this->invoiceId) {
            $errors['invoiceId'] = 'Invalid value: invoiceId';
        }

        if (!$this->description) {
            $errors['description'] = 'Invalid value: description';
        }

        if ($this->qty == 0) {
            $errors['qty'] = 'Invalid value: qty';
        }

        return $errors;
    }

}