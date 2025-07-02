<?php
namespace App\Db;

use App\Db\Traits\CompanyTrait;
use App\Db\Traits\ProductTrait;
use Tk\DataMap\DataMap;
use Tk\DataMap\Db\Date;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Money;
use Tk\Uri;

class Recurring extends Model
{
    use CompanyTrait;
    use ProductTrait;

    const array CYCLE_LIST = [
        Product::CYCLE_WEEK      => 'Weekly',
        Product::CYCLE_FORTNIGHT => 'Fortnightly',
        Product::CYCLE_MONTH     => 'Monthly',
        Product::CYCLE_QUARTER   => 'Quarterly',
        Product::CYCLE_YEAR      => 'Yearly',
        Product::CYCLE_BIANNUAL  => 'Biannually',
    ];

    public int        $recurringId = 0;
    public int        $companyId   = 0;
    public ?int       $productId   = null;
    public Money      $price;
    public int        $count       = 0;
    public string     $cycle        = Product::CYCLE_YEAR;
    public \DateTime  $startOn;
    public ?\DateTime $endOn       = null;
    public ?\DateTime $prevOn      = null;
    public \DateTime  $nextOn;
    public bool       $active      = true;
    public bool       $issue       = true;
    public string     $description = '';
    public string     $notes       = '';
    public ?\DateTime  $modified   = null;
    public ?\DateTime  $created    = null;


    public function __construct()
    {
        $this->startOn = new \DateTime('tomorrow');
        $this->nextOn  = new \DateTime('tomorrow');
        $this->price   = new Money();
    }

    public static function getDataMap(): DataMap
    {
        $map = parent::getDataMap();
        $map->addType(new Date('startOn', 'start_on'));
        $map->addType(new Date('endOn',   'end_on'));
        $map->addType(new Date('prevOn',  'prev_on'));
        $map->addType(new Date('nextOn',  'next_on'));
        return $map;
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->recurringId) {
            $values['recurring_id'] = $this->recurringId;
            Db::update('recurring', 'recurring_id', $values);
        } else {
            unset($values['recurring_id']);
            Db::insert('recurring', $values);
            $this->recurringId = Db::getLastInsertId();
        }

        $this->reload();
    }

    /**
     * Invoice this recurring product to the companies current open invoice
     */
    public function invoice(?\DateTime $now = null): ?Invoice
    {
        if (!$now) $now = \Tk\Date::floor();

        $lastInvoice = $this->prevOn;
        if (!$lastInvoice) {
            $lastInvoice = $now;
        }

        $invoice      = null;
        $this->prevOn = $this->nextOn;
        $this->nextOn = self::createNextDate($this->prevOn, $this->cycle);
        $description  = $this->description . ' [' . $lastInvoice->format(\Tk\Date::FORMAT_AU_DATE) . ' - ' . $this->prevOn->format(\Tk\Date::FORMAT_AU_DATE) . ']';

        if ($this->active) {
            $invoice = Invoice::getOpenInvoice($this->getCompany());

            $code = '';
            if ($this->getProduct()) {
                $code = $this->getProduct()->code;
            }

            $item = InvoiceItem::create($code, $description, $this->price);
            $invoice->addItem($item);
            $this->count++;

            // Notify users
            $users = User::findFiltered(['active' => true, 'type' => User::TYPE_STAFF]);
            foreach ($users as $user) {
                Notify::create(
                    $user->userId,
                    'Recurring product invoiced',
                    $description,
                    Uri::create('/invoiceEdit')->set('invoiceId', $invoice->invoiceId)->toRelativeString(),
                    $user->getImageUrl()
                );
            }
        }

        $this->save();

        return $invoice;
    }

    /**
     * Build a date and add the recurring time difference to the date supplied
     */
    public static function createNextDate(\DateTime $date, string $cycle): \DateTime
    {
        $date = clone $date;
        switch ($cycle) {
            case Product::CYCLE_WEEK:
                $date = $date->add(new \DateInterval('P7D'));
                break;
            case Product::CYCLE_FORTNIGHT:
                $date = $date->add(new \DateInterval('P14D'));
                break;
            case Product::CYCLE_MONTH:
                $date = $date->add(new \DateInterval('P1M'));
                break;
            case Product::CYCLE_QUARTER:
                $date = $date->add(new \DateInterval('P3M'));
                break;
            case Product::CYCLE_YEAR:
                $date = $date->add(new \DateInterval('P1Y'));
                break;
            case Product::CYCLE_BIANNUAL:
                $date = $date->add(new \DateInterval('P2Y'));
                break;
        }
        return \Tk\Date::floor($date);
    }

    /**
     * Deactivate all expired recurring items
     */
    public static function closeExpired(): bool
    {
        $ok = Db::execute("
            UPDATE v_recurring SET
              active = 0,
              notes = CONCAT(notes, '\\nAuto Expired')
            WHERE active
            AND end_on IS NOT NULL
            AND end_on <= CURRENT_DATE");

        return ($ok !== false);
    }

    /**
     * @return array<int,Recurring>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);
        $filter->appendFrom('v_recurring a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.description) LIKE LOWER(:lSearch)';
            $w .= 'OR a.recurring_id = :search';
            $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['recurringId'] = $filter['id'];
        }
        if (!empty($filter['recurringId'])) {
            if (!is_array($filter['recurringId'])) $filter['recurringId'] = [$filter['recurringId']];
            $filter->appendWhere('AND a.recurring_id IN :recurringId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.id NOT IN :exclude');
        }

        if (!empty($filter['companyId'])) {
            if (!is_array($filter['companyId'])) $filter['companyId'] = [$filter['companyId']];
            $filter->appendWhere('AND a.company_id IN :companyId');
        }

        if (!empty($filter['productId'])) {
            if (!is_array($filter['productId'])) $filter['productId'] = [$filter['productId']];
            $filter->appendWhere('AND a.product_id IN :productId');
        }

        if (!empty($filter['cycle'])) {
            $filter->appendWhere('AND a.cycle = :cycle');
        }

        if (is_bool(truefalse($filter['active'] ?? null))) {
            $filter['active'] = truefalse($filter['active']);
            $filter->appendWhere('AND a.active = :active');
        }

        if (is_bool(truefalse($filter['issue'] ?? null))) {
            $filter['issue'] = truefalse($filter['issue']);
            $filter->appendWhere('AND a.issue = :issue');
        }
        if (($filter['isDue'] ?? null) instanceof \DateTime) {
            $filter['isDue'] = $filter['isDue']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('AND a.next_on IS NOT NULL AND a.next_on BETWEEN IFNULL(a.prev_on, DATE(a.created)) AND :isDue');
        } elseif (is_bool(truefalse($filter['isDue'] ?? null))) {
            $filter->appendWhere('AND a.next_on IS NOT NULL AND a.next_on BETWEEN IFNULL(a.prev_on, DATE(a.created)) AND CURRENT_DATE');
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

        if (!$this->companyId) {
            $errors['companyId'] = 'Invalid value: companyId';
        }

        if ($this->productId && !$this->getProduct()) {
            $errors['productId'] = 'Invalid value: productId';
        }

        if (empty($this->productId) && $this->price->getAmount() == 0) {
            $errors['price'] = 'Invalid value: price';
        }

        if (!$this->description) {
            $errors['description'] = 'Invalid value: description';
        }

        if (!$this->cycle) {
            $errors['cycle'] = 'Invalid value: cycle';
        }

        return $errors;
    }

}