<?php
namespace App\Db;

use Bs\Registry;
use Bs\Traits\ForeignModelTrait;
use Bs\Traits\TimestampTrait;
use Tk\DataMap\DataMap;
use Tk\DataMap\Db\Date;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Log;
use Tk\Money;

class Invoice extends Model implements StatusInterface
{
    use TimestampTrait;
    use ForeignModelTrait;

    CONST int DEFAULT_DUE_DAYS = 14;

    const string STATUS_OPEN      = 'open';
    const string STATUS_UNPAID    = 'unpaid';
    const string STATUS_PAID      = 'paid';
    const string STATUS_CANCELLED = 'cancelled';
    const string STATUS_WRITE_OFF = 'write_off';

    const array STATUS_LIST = [
        self::STATUS_OPEN      => 'Open',
        self::STATUS_UNPAID    => 'Unpaid',
        self::STATUS_PAID      => 'Paid',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_WRITE_OFF => 'Write Off',
    ];

    const array STATUS_CSS = [
        self::STATUS_OPEN       => 'secondary',
        self::STATUS_UNPAID     => 'warning',
        self::STATUS_PAID       => 'success',
        self::STATUS_CANCELLED  => 'danger',
        self::STATUS_WRITE_OFF  => 'danger',
    ];

    public int        $invoiceId       = 0;
    //public string     $account         = '';
    public string     $fkey            = '';
    public int        $fid             = 0;
    public string     $purchaseOrder   = '';
    public float      $discount        = 0.0;
    public float      $tax             = 0.0;
    public Money      $subTotal;
    public Money      $shipping;
    public Money      $total;
    public string     $status          = self::STATUS_OPEN;
    public string     $billingAddress  = '';
    public string     $shippingAddress = '';
    public ?\DateTime $issuedOn        = null;
    public ?\DateTime $paidOn          = null;
    public string     $notes           = '';
    public \DateTime  $modified;
    public \DateTime  $created;


    public function __construct()
    {
        $this->subTotal = Money::create();
        $this->shipping = Money::create();
        $this->total    = Money::create();
        $this->modified = new \DateTime();
        $this->created  = new \DateTime();
    }

    public static function getDataMap(): DataMap
    {
        $map = parent::getDataMap();
        $map->addType(new Date('issuedOn', 'issued_on'));
        $map->addType(new Date('paidOn', 'paid_on'));
        return $map;
    }

    /**
     * Create from a company
     */
    public static function create(Model $client): static
    {
        $invoice = new static();
        $invoice->setDbModel($client);
        return $invoice;
    }

    public static function getOpenInvoice(Model $client): ?static
    {
        $invoice = null;
        try {
            // search for an existing open invoice for this account
            $invoice = self::findOpenInvoice($client);
            if (!($invoice instanceof Invoice)) {
                $invoice = static::create($client);
                $invoice->save();
            }
        } catch (\Exception $e) {
            \Tk\Log::error($e->getMessage());
        }
        return $invoice;
    }

    public function save(): void
    {
        $map = static::getDataMap();

        // calculate and set totals
        $this->calculateTotal();

        $values = $map->getArray($this);
        if ($this->invoiceId) {
            $values['invoice_id'] = $this->invoiceId;
            Db::update('invoice', 'invoice_id', $values);
        } else {
            unset($values['invoice_id']);
            Db::insert('invoice', $values);
            $this->invoiceId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public function getCompany(): ?Company
    {
        $model = $this->getDbModel();
        if ($model instanceof Company) {
            return $model;
        }
        return null;
    }

    /**
     * @return array<int,InvoiceItem>
     */
    public function getItemList(): array
    {
        return InvoiceItem::findFiltered(Filter::create(['invoiceId' => $this->invoiceId], '-created'));
    }

    public function addItem(InvoiceItem $item): static
    {
        if ($this->getStatus() != self::STATUS_OPEN) {
            throw new \Tk\Exception('Invoice is not open and cannot be modified');
        }

        $item->invoiceId = $this->invoiceId;
        $item->save();
        $this->save();      // Recalculate totals.

        return $this;
    }

    public function deleteItem(InvoiceItem $item): static
    {
        if (!$this->getStatus() == self::STATUS_OPEN) {
            throw new \Tk\Exception('Invoice is not open and cannot be modified');
        }

        $item->delete();
        $this->save();      // Recalculate totals.

        return $this;
    }

    public function calculateTotal(): Money
    {
        $this->subTotal = $this->calculateSubTotal();
        $this->total = $this->subTotal->subtract($this->getDiscountTotal());
        $this->total = $this->total->add($this->getTaxTotal());
        $this->total = $this->total->add($this->shipping);
        return $this->total;
    }

    protected function calculateSubTotal(): Money
    {
        $total = Money::create();
        foreach ($this->getItemList() as $item) {
            $total = $total->add($item->total);
        }
        return $total;
    }

    /**
     * Get the total of the invoice
     *  o subtotal * discount = discount
     */
    function getDiscountTotal(): Money
    {
        return $this->subTotal->multiply($this->discount);
    }

    /**
     * Get the total of the invoice
     *  o subtotal * tax = tax
     */
    function getTaxTotal(): Money
    {
        $discount = $this->subTotal->multiply($this->discount);
        $total = $this->subTotal->subtract($discount);
        return $total->multiply($this->tax);
    }

    public static function getUnpaidTotal(): Money
    {
        $total = \Tk\Money::create();
        $list = self::findFiltered(Filter::create(['status' => self::STATUS_UNPAID]));
        foreach ($list as $invoice) {
            $total = $total->add($invoice->getOutstandingAmount());
        }
        return $total;
    }

    public static function getOpenTotal(): Money
    {
        $total = \Tk\Money::create();
        $list = self::findFiltered(Filter::create(['status' => self::STATUS_OPEN]));
        foreach ($list as $invoice) {
            $total = $total->add($invoice->getOutstandingAmount());
        }
        return $total;
    }

    /**
     * @return array<int,Payment>
     */
    public function getPaymentList(): array
    {
        return Payment::findFiltered(['invoiceId' => $this->invoiceId], '-created');
    }

    public function getPaymentTotal(): Money
    {
        $total = \Tk\Money::create();
        foreach ($this->getPaymentList() as $payment) {
            $total = $total->add($payment->amount);
        }
        return $total;
    }

    public function getOutstandingAmount(): Money
    {
        return $this->total->subtract($this->getPaymentTotal());
    }

    public function addPayment(Payment $payment): static
    {
        if ($this->getStatus() != self::STATUS_UNPAID) {
            throw new \Tk\Exception('Invoice is not unpaid and payments cannot be modified');
        }

        $this->save();      // Recalculate totals.
        if ($payment->amount->getAmount() <= 0 || $this->getOutstandingAmount()->lessThan($payment->amount)) {
            throw new \Tk\Exception('Payment amount must be greater than 0 and less than the outstanding amount: ' . $this->getOutstandingAmount()->toString());
        }
        $payment->invoiceId = $this->invoiceId;
        $payment->save();
        $this->save();      // Recalculate totals.

        // Check if invoice is paid then change the invoice status to paid
        if ($this->getOutstandingAmount()->getAmount() == 0) {
            $this->status = self::STATUS_PAID;
            $this->paidOn = $payment->receivedAt;
            $this->save();
        }

        return $this;
    }

    public function deletePayment(Payment $payment): static
    {
        if (!in_array($this->status, [self::STATUS_OPEN, self::STATUS_UNPAID])) {
            throw new \Tk\Exception('Invoice must be unpaid or open for payments to be modified');
        }

        $payment->delete();
        $this->save();      // Recalculate totals.

        return $this;
    }

    public function doIssue(): static
    {
        if ($this->getOutstandingAmount()->getAmount() <= 0) {
            \Tk\Log::warning('Invoice not issued as there is no outstanding amount: ID: ' . $this->invoiceId);
            return $this;
        }
        $this->status = self::STATUS_UNPAID;
        $this->issuedOn = \Tk\Date::create();
        $this->save();
        return $this;
    }

    public function doCancel(): static
    {
        $this->status = self::STATUS_CANCELLED;
        $this->save();
        return $this;
    }

    public function doWriteOff(): static
    {
        if ($this->getOutstandingAmount()->getAmount() <= 0) {
            throw new \Tk\Exception('Cannot issue an invoice without an outstanding amount.');
        }
        $this->status = self::STATUS_WRITE_OFF;
        $this->save();
        return $this;
    }

    /**
     * @todo `account.due.days` is not in the registry or settings page
     */
    public function getDateDue(): ?\DateTime
    {
        $due = null;
        if ($this->issuedOn) {
            $days = intval(Registry::instance()->get('account.due.days', self::DEFAULT_DUE_DAYS));
            $interval = new \DateInterval('P' . $days . 'D');
            $due = $this->issuedOn->add($interval);
        }
        return $due;
    }

    public function isOverdue(): bool
    {
        if (is_null($this->issuedOn)) return false;
        $now = \Tk\Date::floor();
        return $now > $this->getDateDue();
    }


    public static function find(int $invoiceId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM invoice
            WHERE invoice_id = :invoiceId",
            compact('invoiceId'),
            self::class
        );
    }

    /**
     * @return array<int,Invoice>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM invoice",
            [],
            self::class
        );
    }

    public static function findOpenInvoice(Model $client): ?static
    {
        return self::findFiltered(Filter::create([
            'model' => $client,
            'status' => self::STATUS_OPEN
        ], '-created'))[0] ?? null;
    }

    /**
     * @return array<int,Invoice>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w = '';
            //$w .= 'LOWER(a.name) LIKE LOWER(:search) OR ';
            $w .= 'a.invoice_id = :search OR ';
            if (is_numeric($filter['search'])) {
                $w .= 'a.invoice_id = :search OR ';
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['invoiceId'] = $filter['id'];
        }
        if (!empty($filter['invoiceId'])) {
            if (!is_array($filter['invoiceId'])) $filter['invoiceId'] = [$filter['invoiceId']];
            $filter->appendWhere('a.invoice_id IN :invoiceId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.invoice_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['model']) && $filter['model'] instanceof Model) {
            $filter['fid'] = self::getDbModelId($filter['model']);
            $filter['fkey'] = get_class($filter['model']);
        }
        if (isset($filter['fid'])) {
            $filter->appendWhere('a.fid = :fid AND ');
        }
        if (isset($filter['fkey'])) {
            $filter->appendWhere('a.fkey = :fkey AND ');
        }

        if (!empty($filter['status'])) {
            if (!is_array($filter['status'])) $filter['status'] = [$filter['status']];
            $filter->appendWhere('a.status IN :status AND ', $filter['status']);
        }

        if (!empty($filter['dateStart'])) {
            if (($filter['dateStart'] instanceof \DateTime)) $filter['dateStart'] = $filter['dateStart']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('a.created >= :dateStart AND ');
        }
        if (!empty($filter['dateEnd'])) {
            if (($filter['dateEnd'] instanceof \DateTime)) $filter['dateEnd'] = $filter['dateEnd']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('a.created <= :dateEnd AND ');
        }

        if (!empty($filter['dateIssuedStart'])) {
            if (($filter['dateIssuedStart'] instanceof \DateTime)) $filter['dateIssuedStart'] = $filter['dateIssuedStart']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('a.date_issued >= :dateIssuedStart AND ');
        }
        if (!empty($filter['dateIssuedEnd'])) {
            if (($filter['dateIssuedEnd'] instanceof \DateTime)) $filter['dateIssuedEnd'] = $filter['dateIssuedEnd']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('a.date_issued <= :dateIssuedEnd AND ');
        }

        if (!empty($filter['datePaidStart'])) {
            if (($filter['datePaidStart'] instanceof \DateTime)) $filter['datePaidStart'] = $filter['datePaidStart']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('a.date_paid >= :datePaidStart AND ');
        }
        if (!empty($filter['datePaidEnd'])) {
            if (($filter['datePaidEnd'] instanceof \DateTime)) $filter['datePaidEnd'] = $filter['datePaidEnd']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('a.date_paid <= :datePaidEnd AND ');
        }

        return Db::query("
            SELECT *
            FROM invoice a
            {$filter->getSql()}",
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

        if (!$this->fkey) {
            $errors['client'] = 'Invalid client type selected';
        }

        if (!$this->fid) {
            $errors['client'] = 'Invalid client selected';
        }

        if ($this->total->getAmount() == 0) {
            $errors['total'] = 'Invalid value: total';
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
        if ($statusLog->name == self::STATUS_UNPAID && $prevStatusName == self::STATUS_OPEN) {
            if (!\App\Email\Invoice::sendIssueInvoice($this)) {     // on invoice issue
                Log::error("failed to send invoice email to {$this->fkey} ID {$this->fid}");
            }
        }
    }
}