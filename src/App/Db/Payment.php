<?php
namespace App\Db;

use App\Db\Traits\InvoiceTrait;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Log;
use Tk\Money;

class Payment extends Model implements StatusInterface
{
    use InvoiceTrait;

    const string STATUS_CLEARED    = 'cleared';
    const string STATUS_PENDING    = 'pending';
    const string STATUS_CANCELLED  = 'cancelled';

    const array STATUS_LIST = [
        self::STATUS_CLEARED    => 'Cleared',
        self::STATUS_PENDING    => 'Pending',
        self::STATUS_CANCELLED  => 'Cancelled',
    ];

    const string METHOD_CASH    = 'cash';
    const string METHOD_DEPOSIT = 'eft';
    const string METHOD_CARD    = 'card';
    const string METHOD_CRYPTO  = 'crypto';
    const string METHOD_OTHER   = 'other';

    const array METHOD_LIST = [
        self::METHOD_CASH    => 'Cash',
        self::METHOD_DEPOSIT => 'EFT',
        self::METHOD_CARD    => 'Credit Card',
        self::METHOD_CRYPTO  => 'Crypto',
        self::METHOD_OTHER   => 'Other',
    ];


    public int        $paymentId  = 0;
    public int        $invoiceId  = 0;
    public Money      $amount;
    public string     $method     = self::METHOD_DEPOSIT;
    public string     $status     = self::STATUS_CLEARED;
    public \DateTime  $receivedAt;
    public ?string    $notes      = null;
    public \DateTime  $modified;
    public \DateTime  $created;


    public function __construct()
    {
        $this->amount = Money::create();
        $this->receivedAt = new \DateTime();
        $this->modified = new \DateTime();
        $this->created = new \DateTime();
    }

    public static function create(Money $amount, string $method = self::METHOD_CASH, string $status = self::STATUS_CLEARED): self
    {
        $obj = new self();
        $obj->amount = $amount;
        $obj->method = $method;
        $obj->status = $status;
        $obj->receivedAt = \Tk\Date::create();
        return $obj;
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->paymentId) {
            $values['payment_id'] = $this->paymentId;
            Db::update('payment', 'payment_id', $values);
        } else {
            unset($values['payment_id']);
            Db::insert('payment', $values);
            $this->paymentId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public function delete(): bool
    {
        return (false !== Db::delete('payment', ['payment_id' => $this->paymentId]));
    }

    public static function find(int $paymentId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM payment
            WHERE payment_id = :paymentId",
            compact('paymentId'),
            self::class
        );
    }

    /**
     * @return array<int,Payment>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM payment",
            [],
            self::class
        );
    }

    /**
     * @return array<int,Payment>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);
        $filter->appendFrom('payment a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.notes) LIKE LOWER(:lSearch)';
            $w .= 'OR a.payment_id = :search';
            $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['paymentId'] = $filter['id'];
        }
        if (!empty($filter['paymentId'])) {
            if (!is_array($filter['paymentId'])) $filter['paymentId'] = [$filter['paymentId']];
            $filter->appendWhere('AND a.payment_id IN :paymentId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.payment_id NOT IN :exclude', $filter['exclude']);
        }

        if (!empty($filter['invoiceId'])) {
            $filter->appendWhere('AND a.invoice_id = :invoiceId');
        }

        if (!empty($filter['method'])) {
            $filter->appendWhere('AND a.method = :method');
        }

        if (!empty($filter['status'])) {
            $filter->appendWhere('AND a.status = :status');
        }

        if (!empty($filter['dateStart'])) {
            if (($filter['dateStart'] instanceof \DateTime)) $filter['dateStart'] = $filter['dateStart']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('AND a.received_at >= :dateStart');
        }
        if (!empty($filter['dateEnd'])) {
            if (($filter['dateEnd'] instanceof \DateTime)) $filter['dateEnd'] = $filter['dateEnd']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('AND a.received_at <= :dateEnd');
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

        if (!$this->paymentId) {
            $errors['paymentId'] = 'Invalid value: paymentId';
        }

        if (!$this->invoiceId) {
            $errors['invoiceId'] = 'Invalid value: invoiceId';
        }

        if ($this->amount->getAmount() == 0) {
            $errors['amount'] = 'Invalid value: amount';
        }

        return $errors;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function onStatusChanged(StatusLog $statusLog): void
    {
        $prevStatusName = $statusLog->getPreviousName();
        if ($statusLog->name == self::STATUS_CLEARED && (!$prevStatusName || $prevStatusName == self::STATUS_PENDING)) {
            if (!$statusLog->notify) return;
            if (!\App\Email\Invoice::sendPaymentReceipt($this)) {       // email client payment receipt
                Log::error("failed to send payment receipt for invoice {$this->getInvoice()->fkey} ID {$this->getInvoice()->fid}");
            }
        }
    }

}