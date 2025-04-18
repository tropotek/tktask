<?php
namespace App\Db;

use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Money;

class Company extends Model
{
    const string TYPE_CLIENT   = 'Client';
    const string TYPE_SUPPLIER = 'Supplier';

    const array TYPE_LIST = [
        self::TYPE_CLIENT,
        self::TYPE_SUPPLIER,
    ];

    public int     $companyId     = 0;
    public string  $accountId     = '';
    public ?string $type          = self::TYPE_CLIENT;
    public string  $name          = '';
    public string  $alias         = '';
    public string  $abn           = '';
    public string  $website       = '';
    public string  $contact       = '';
    public string  $phone         = '';
    public string  $email         = '';
    public string  $accountsEmail = '';
    public string  $address       = '';
    public string  $notes         = '';
    public bool    $active        = true;

    public Money              $credit;
    public \DateTimeImmutable $modified;
    public \DateTimeImmutable $created;


    public function __construct()
    {
        $this->credit   = Money::create();
        $this->modified = new \DateTimeImmutable();
        $this->created  = new \DateTimeImmutable();
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->companyId) {
            $values['company_id'] = $this->companyId;
            Db::update('company', 'company_id', $values);
        } else {
            unset($values['company_id']);
            Db::insert('company', $values);
            $this->companyId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public static function find(int $companyId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM v_company
            WHERE company_id = :companyId",
            compact('companyId'),
            self::class
        );
    }

    /**
     * @return array<int,Company>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM v_company",
            [],
            self::class
        );
    }

    /**
     * @return array<int,Company>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.name) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.alias) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.abn) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.contact) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.email) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.accounts_email) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.phone) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.website) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.account_id) LIKE LOWER(:search) OR ';
            $w .= 'a.company_id = :search OR ';
            if (is_numeric($filter['search'])) {
                $w .= 'a.company_id = :search OR ';
            }
            $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['companyId'] = $filter['id'];
        }
        if (!empty($filter['companyId'])) {
            if (!is_array($filter['companyId'])) $filter['companyId'] = [$filter['companyId']];
            $filter->appendWhere('a.company_id IN :companyId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.company_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['accountId'])) {
            $filter->appendWhere('a.account_id = :accountId AND ');
        }
        if (!empty($filter['type'])) {
            $filter->appendWhere('a.type = :type AND ');
        }
        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = :name AND ');
        }
        if (!empty($filter['alias'])) {
            $filter->appendWhere('a.alias = :alias AND ');
        }
        if (!empty($filter['abn'])) {
            $filter->appendWhere('a.abn = :abn AND ');
        }
        if (!empty($filter['website'])) {
            $filter->appendWhere('a.website = :website AND ');
        }
        if (!empty($filter['contact'])) {
            $filter->appendWhere('a.contact = :contact AND ');
        }
        if (!empty($filter['phone'])) {
            $filter->appendWhere('a.phone = :phone AND ');
        }
        if (!empty($filter['email'])) {
            $filter->appendWhere('a.email = :email AND ');
        }
        if (!empty($filter['address'])) {
            $filter->appendWhere('a.address = :address AND ');
        }
        if (isset($filter['hasCredit'])) {
            $filter->appendWhere('a.credit > 0 AND ');
        }
        if (is_bool(truefalse($filter['active'] ?? null))) {
            $filter['active'] = truefalse($filter['active']);
            $filter->appendWhere('a.active = :active AND ');
        }

        return Db::query("
            SELECT *
            FROM v_company a
            {$filter->getSql()}",
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

        if (!in_array($this->type, self::TYPE_LIST)) {
            $errors['type'] = 'Invalid value: type';
        }

        if (!$this->name) {
            $errors['name'] = 'Invalid value: name';
        }

        if ($this->type == self::TYPE_CLIENT && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Clients must have an email';
        }
        if ($this->email && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid value: email';
        }
        if ($this->accountsEmail && !filter_var($this->accountsEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['accountsEmail'] = 'Invalid value: accountsEmail';
        }

        return $errors;
    }

}