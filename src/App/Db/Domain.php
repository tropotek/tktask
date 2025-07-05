<?php
namespace App\Db;

use App\Db\Traits\CompanyTrait;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\FileUtil;
use Tk\Uri;

class Domain extends Model
{
    use CompanyTrait;

    public int        $domainId    = 0;
    public int        $companyId   = 0;
    public string     $companyName = '';
    public string     $url         = '';
    public ?string    $notes       = null;
    public bool       $active      = true;
    public bool       $status      = true;   // current status
    public string     $siteName    = '';
    public int        $bytes       = 0;
    public int        $lastPing_id = 0;
    public ?\DateTime $pingedAt    = null;
    public ?\DateTime $modified    = null;
    public ?\DateTime $created     = null;


    public function __construct()
    {
    }

    public static function pingAllDomains(): bool
    {
        $domains = self::findFiltered(['active' => true]);
        foreach ($domains as $domain) {
            if (!self::pingDomain($domain)) {
                \App\Email\Domain::sendServerOfflineNotice($domain);
            }
        }

        return true;
    }

    private static function pingDomain(self $domain): bool
    {
        $url = Uri::create($domain->url);
        $opts = [
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ];
        $context = stream_context_create($opts);
        $retries = 0;
        do {
            $data = file_get_contents($url->toString(), false, $context);
            $retries++;
        } while ($retries <= 4 && $data === false);

        if ($data === false) {
            DomainPing::create($domain->domainId, false);
            return false;
        } else {
            if (basename($url->getPath()) == 'tkping') {
                $data = json_decode($data, true);
                DomainPing::create($domain->domainId, true, $data);
            } else {
                // standard host with no data
                DomainPing::create($domain->domainId, true);
            }
            return true;
        }
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->domainId) {
            $values['domain_id'] = $this->domainId;
            Db::update('domain', 'domain_id', $values);
        } else {
            unset($values['domain_id']);
            Db::insert('domain', $values);
            $this->domainId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public function getLastPing(): ?DomainPing
    {
        return DomainPing::find($this->lastPing_id);
    }

    /**
     * @return array<int,Domain>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);
        $filter->appendFrom(static::getPrimaryTable() . ' a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.url) LIKE LOWER(:lSearch)';
            $w .= 'OR a.domain_id = :search';
            $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['domainId'] = $filter['id'];
        }
        if (!empty($filter['domainId'])) {
            if (!is_array($filter['domainId'])) $filter['domainId'] = [$filter['domainId']];
            $filter->appendWhere('AND a.domain_id IN :domainId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.domain_id NOT IN :exclude', $filter['exclude']);
        }

        if (is_bool(truefalse($filter['status'] ?? null))) {
            $filter['status'] = truefalse($filter['status']);
            $filter->appendWhere('AND a.status = :status');
        }

        if (is_bool(truefalse($filter['active'] ?? null))) {
            $filter['active'] = truefalse($filter['active']);
            $filter->appendWhere('AND a.active = :active');
        }

        if (!empty($filter['companyId'])) {
            $filter->appendWhere('AND a.company_id = :companyId');
        }

        if (!empty($filter['always'])) {
            if (!is_array($filter['always'])) $filter['always'] = [$filter['always']];
            $filter->appendWhere('OR (a.domain_id IN :always)', $filter['always']);
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

        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            $errors['url'] = 'Invalid value: url';
        }

        return $errors;
    }

}