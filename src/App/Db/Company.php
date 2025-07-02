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

    public int        $companyId     = 0;
    public string     $accountId     = '';
    public ?string    $type          = self::TYPE_CLIENT;
    public string     $name          = '';
    public string     $alias         = '';
    public string     $abn           = '';
    public string     $website       = '';
    public string     $contact       = '';
    public string     $phone         = '';
    public string     $email         = '';
    public string     $accountsEmail = '';
    public string     $address       = '';
    public string     $notes         = '';
    public bool       $active        = true;
    public Money      $credit;
    public ?\DateTime $modified      = null;
    public ?\DateTime $created       = null;


    public function __construct()
    {
        $this->credit = Money::create();
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

    /**
     * @return array<int,Company>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);
        $filter->appendFrom('v_company a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.name) LIKE LOWER(:lSearch)';
            $w .= 'OR LOWER(a.alias) LIKE LOWER(:lSearch)';
            $w .= 'OR LOWER(a.abn) LIKE LOWER(:lSearch)';
            $w .= 'OR LOWER(a.contact) LIKE LOWER(:lSearch)';
            $w .= 'OR LOWER(a.email) LIKE LOWER(:lSearch)';
            $w .= 'OR LOWER(a.accounts_email) LIKE LOWER(:lSearch)';
            $w .= 'OR LOWER(a.phone) LIKE LOWER(:lSearch)';
            $w .= 'OR LOWER(a.website) LIKE LOWER(:lSearch)';
            $w .= 'OR LOWER(a.account_id) LIKE LOWER(:lSearch)';
            $w .= 'OR a.company_id = :search';
            $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['companyId'] = $filter['id'];
        }
        if (!empty($filter['companyId'])) {
            if (!is_array($filter['companyId'])) $filter['companyId'] = [$filter['companyId']];
            $filter->appendWhere('AND a.company_id IN :companyId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.company_id NOT IN :exclude', $filter['exclude']);
        }

        if (!empty($filter['accountId'])) {
            $filter->appendWhere('AND a.account_id = :accountId');
        }
        if (!empty($filter['type'])) {
            $filter->appendWhere('AND a.type = :type');
        }
        if (!empty($filter['name'])) {
            $filter->appendWhere('AND a.name = :name');
        }
        if (!empty($filter['alias'])) {
            $filter->appendWhere('AND a.alias = :alias');
        }
        if (!empty($filter['abn'])) {
            $filter->appendWhere('AND a.abn = :abn');
        }
        if (!empty($filter['website'])) {
            $filter->appendWhere('AND a.website = :website');
        }
        if (!empty($filter['contact'])) {
            $filter->appendWhere('AND a.contact = :contact');
        }
        if (!empty($filter['phone'])) {
            $filter->appendWhere('AND a.phone = :phone');
        }
        if (!empty($filter['email'])) {
            $filter->appendWhere('AND a.email = :email');
        }
        if (!empty($filter['address'])) {
            $filter->appendWhere('AND a.address = :address');
        }
        if (isset($filter['hasCredit'])) {
            $filter->appendWhere('AND a.credit > 0');
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