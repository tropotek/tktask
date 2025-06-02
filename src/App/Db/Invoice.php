<?php
namespace App\Db;

use Bs\Registry;
use Bs\Traits\ForeignModelTrait;
use Tk\DataMap\DataMap;
use Tk\DataMap\Db\Date;
use Tk\DataMap\Form\Percent;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Log;
use Tk\Money;
use Tk\Uri;

class Invoice extends Model
{
    use ForeignModelTrait;

    CONST int DEFAULT_OVERDUE_DAYS = 14;

    const string STATUS_OPEN      = 'open';
    const string STATUS_UNPAID    = 'unpaid';
    const string STATUS_PAID      = 'paid';
    const string STATUS_CANCELLED = 'cancelled';

    const array STATUS_LIST = [
        self::STATUS_OPEN      => 'Open',
        self::STATUS_UNPAID    => 'Unpaid',
        self::STATUS_PAID      => 'Paid',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    const array STATUS_CSS = [
        self::STATUS_OPEN       => 'secondary',
        self::STATUS_UNPAID     => 'warning',
        self::STATUS_PAID       => 'success',
        self::STATUS_CANCELLED  => 'danger',
    ];

    public int        $invoiceId       = 0;
    public string     $fkey            = '';
    public int        $fid             = 0;
    public string     $purchaseOrder   = '';
    public float      $discount        = 0.0;
    public float      $tax             = 0.0;
    public Money      $shipping;
    public Money      $subTotal;
    public Money      $discountTotal;
    public Money      $taxTotal;
    public Money      $total;
    public Money      $paidTotal;
    public Money      $unpaidTotal;
    public string     $status          = '';
    public string     $billingAddress  = '';
    public ?\DateTime $issuedOn        = null;
    public ?\DateTime $paidOn          = null;
    public ?\DateTime $cancelledOn     = null;
    public string     $notes           = '';
    public \DateTime  $modified;
    public \DateTime  $created;


    public function __construct()
    {
        $this->shipping      = Money::create();
        $this->subTotal      = Money::create();
        $this->discountTotal = Money::create();
        $this->taxTotal      = Money::create();
        $this->total         = Money::create();
        $this->paidTotal     = Money::create();
        $this->unpaidTotal   = Money::create();

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

    public static function getFormMap(): DataMap
    {
        $map = parent::getFormMap();
        $map->addType(new Percent('discount'));
        $map->addType(new Percent('tax'));
        return $map;
    }

    /**
     * Create from a company
     */
    public static function create(Model $client): self
    {
        $invoice = new self();
        $invoice->setDbModel($client);
        return $invoice;
    }

    public static function getOpenInvoice(Model $client): ?self
    {
        $invoice = null;
        try {
            // search for an existing open invoice for this account
            $invoice = self::findOpenInvoice($client);
            if (!($invoice instanceof Invoice)) {
                $invoice = self::create($client);
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
        if ($this->status != self::STATUS_OPEN) {
            throw new \Tk\Exception('Invoice is not open and cannot be modified');
        }

        $item->invoiceId = $this->invoiceId;
        $item->save();
        $this->reload();

        return $this;
    }

    public function deleteItem(InvoiceItem $item): static
    {
        if (!$this->status == self::STATUS_OPEN) {
            throw new \Tk\Exception('Invoice is not open and cannot be modified');
        }

        $item->delete();
        $this->reload();

        return $this;
    }

    /**
     * @return array<int,Payment>
     */
    public function getPaymentList(): array
    {
        return Payment::findFiltered(Filter::create(['invoiceId' => $this->invoiceId], '-created'));
    }

    public function addPayment(Payment $payment): static
    {
        if ($this->status != self::STATUS_UNPAID) {
            throw new \Tk\Exception('Invoice is not unpaid and payments cannot be modified');
        }

        if ($payment->amount->getAmount() <= 0 || $this->unpaidTotal->lessThan($payment->amount)) {
            throw new \Tk\Exception('Payment amount must be greater than 0 and less than the outstanding amount: ' . $this->unpaidTotal->toString());
        }

        $payment->invoiceId = $this->invoiceId;
        $payment->save();
        $this->reload();      // Recalculate totals.

        // Check if invoice is paid then change the invoice status to paid
        if ($this->unpaidTotal->getAmount() == 0) {
            $this->paidOn = $payment->receivedAt;
            $this->save();

            if (!\App\Email\Invoice::sendPaymentReceipt($payment)) {    // email client payment receipt
                Log::error("failed to send payment receipt for invoice {$this->fkey} ID {$this->fid}");
            }
        }

        return $this;
    }

    public function deletePayment(Payment $payment): static
    {
        if (!in_array($this->status, [self::STATUS_OPEN, self::STATUS_UNPAID])) {
            throw new \Tk\Exception('Invoice must be unpaid or open for payments to be modified');
        }

        $payment->delete();
        $this->reload();      // Recalculate totals.

        return $this;
    }

    public function doIssue(): static
    {

        $this->issuedOn = \Tk\Date::create();
        $this->save();

        if ($this->unpaidTotal->getAmount() <= 0) {
            \Tk\Log::warning('Invoice not issued as there is no outstanding amount: ID: ' . $this->invoiceId);
            return $this;
        }

        if (!\App\Email\Invoice::sendIssueInvoice($this)) {     // on invoice issue
            Log::error("failed to send invoice email to {$this->fkey} ID {$this->fid}");
        }

        // Notify users
        $users = User::findFiltered(['active' => true, 'type' => User::TYPE_STAFF]);
        foreach ($users as $user) {
            Notify::create(
                $user->userId,
                'Invoice issued',
                sprintf('Invoice #%s issued to %s', $this->invoiceId, $this->getCompany()->name),
                Uri::create('/invoiceEdit')->set('invoiceId', $this->invoiceId)->toRelativeString(),
                $user->getImageUrl()
            );
        }

        return $this;
    }

    public function reopen(): static
    {
        $this->issuedOn = null;
        $this->paidOn = null;
        $this->cancelledOn = null;
        $this->save();

        return $this;
    }

    public function doCancel(): static
    {
        $this->cancelledOn = \Tk\Date::create();
        $this->save();
        return $this;
    }

    public function getDateDue(): ?\DateTime
    {
        $due = null;
        if ($this->issuedOn) {
            $days = (int)Registry::getValue('site.account.overdue.days', self::DEFAULT_OVERDUE_DAYS);
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

    public function getOutstanding(): array
    {
        return self::findOutstanding($this->invoiceId);
    }

    public function getOutstandingAmount(): Money
    {
        $outstanding = $this->getOutstanding();
        $total = new Money();
        foreach ($outstanding as $invoice) {
            $total = $total->add($invoice->unpaidTotal);
        }
        return $total;
    }

    /**
     * Return any outstanding invoice from the same company as the supplied invoiceId
     * Does not include the supplied invoiceId
     * @return array<int,Invoice>
     */
    public static function findOutstanding(int $invoiceId, array|Filter $filter = []): array
    {
        $filter = Filter::create($filter);
        $filter->replace([
            'invoiceId' => $invoiceId,
            'status' => self::STATUS_UNPAID,
        ]);
        $filter->appendWhere('AND i.invoice_id != ex.invoice_id');
        $filter->appendWhere('AND i.status = :status');

        return Db::query("
            SELECT i.*
            FROM v_invoice i
            LEFT JOIN v_invoice ex ON (ex.fkey = i.fkey AND ex.fid = i.fid AND ex.invoice_id = :invoiceId)
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

    public static function getFirstInvoice(): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM v_invoice
            ORDER BY invoice_id
            LIMIT 1",
            [],
            self::class
        );
    }

    public static function findOpenInvoice(Model $client): ?self
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
        $filter->appendFrom('v_invoice a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.purchase_order) LIKE LOWER(:lSearch)';
            $w .= 'OR a.invoice_id = :search';
            $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['invoiceId'] = $filter['id'];
        }
        if (!empty($filter['invoiceId'])) {
            if (!is_array($filter['invoiceId'])) $filter['invoiceId'] = [$filter['invoiceId']];
            $filter->appendWhere('AND a.invoice_id IN :invoiceId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.invoice_id NOT IN :exclude', $filter['exclude']);
        }

        if (!empty($filter['model']) && $filter['model'] instanceof Model) {
            $filter['fid'] = self::getDbModelId($filter['model']);
            $filter['fkey'] = get_class($filter['model']);
        }
        if (isset($filter['fid'])) {
            $filter->appendWhere('AND a.fid = :fid');
        }
        if (isset($filter['fkey'])) {
            $filter->appendWhere('AND a.fkey = :fkey');
        }

        if (!empty($filter['status'])) {
            if (!is_array($filter['status'])) $filter['status'] = [$filter['status']];
            $filter->appendWhere('AND  a.status IN :status', $filter['status']);
        }

        if (!empty($filter['dateStart'])) {
            if (($filter['dateStart'] instanceof \DateTime)) $filter['dateStart'] = $filter['dateStart']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('AND a.created >= :dateStart');
        }
        if (!empty($filter['dateEnd'])) {
            if (($filter['dateEnd'] instanceof \DateTime)) $filter['dateEnd'] = $filter['dateEnd']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('AND a.created <= :dateEnd');
        }

        if (!empty($filter['dateIssuedStart'])) {
            if (($filter['dateIssuedStart'] instanceof \DateTime)) $filter['dateIssuedStart'] = $filter['dateIssuedStart']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('AND a.date_issued >= :dateIssuedStart');
        }
        if (!empty($filter['dateIssuedEnd'])) {
            if (($filter['dateIssuedEnd'] instanceof \DateTime)) $filter['dateIssuedEnd'] = $filter['dateIssuedEnd']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('AND a.date_issued <= :dateIssuedEnd');
        }

        if (!empty($filter['datePaidStart'])) {
            if (($filter['datePaidStart'] instanceof \DateTime)) $filter['datePaidStart'] = $filter['datePaidStart']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('AND a.date_paid >= :datePaidStart');
        }
        if (!empty($filter['datePaidEnd'])) {
            if (($filter['datePaidEnd'] instanceof \DateTime)) $filter['datePaidEnd'] = $filter['datePaidEnd']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('AND a.date_paid <= :datePaidEnd');
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

}