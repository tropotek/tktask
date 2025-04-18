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

    public int       $domainId    = 0;
    public int       $companyId   = 0;
    public string    $companyName = '';
    public string    $url         = '';
    public ?string   $notes       = null;
    public bool      $active      = true;
    public bool      $status      = true;   // current status
    public string    $siteName    = '';
    public int       $bytes       = 0;
    public int       $lastPing_id = 0;
    public \DateTime $modified;
    public \DateTime $created;


    public function __construct()
    {
        $this->modified = new \DateTime();
        $this->created  = new \DateTime();
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

    public static function find(int $domainId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM v_domain
            WHERE domain_id = :domainId",
            compact('domainId'),
            self::class
        );
    }

    /**
     * @return array<int,Domain>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM v_domain",
            [],
            self::class
        );
    }

    /**
     * @return array<int,Domain>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w = '';
            $w .= 'LOWER(a.url) LIKE LOWER(:search) OR ';
            $w .= 'a.domain_id = :search OR ';
            if (is_numeric($filter['search'])) {
                $w .= 'a.domain_id = :search OR ';
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['always'])) {
            if (!is_array($filter['always'])) $filter['always'] = [$filter['always']];
            $filter->appendWhere('(a.domain_id IN :always) OR ', $filter['always']);
        }

        if (!empty($filter['id'])) {
            $filter['domainId'] = $filter['id'];
        }
        if (!empty($filter['domainId'])) {
            if (!is_array($filter['domainId'])) $filter['domainId'] = [$filter['domainId']];
            $filter->appendWhere('a.domain_id IN :domainId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.domain_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (is_bool(truefalse($filter['status'] ?? null))) {
            $filter['status'] = truefalse($filter['status']);
            $filter->appendWhere('a.status = :status AND ');
        }

        if (is_bool(truefalse($filter['active'] ?? null))) {
            $filter['active'] = truefalse($filter['active']);
            $filter->appendWhere('a.active = :active AND ');
        }

        if (!empty($filter['companyId'])) {
            $filter->appendWhere('a.company_id = :companyId AND ');
        }

        return Db::query("
            SELECT *
            FROM v_domain a
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

        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            $errors['url'] = 'Invalid value: url';
        }

        return $errors;
    }

}