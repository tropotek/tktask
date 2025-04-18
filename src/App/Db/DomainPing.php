<?php
namespace App\Db;

use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;

class DomainPing extends Model
{
    const bool STATUS_UP = true;
    const bool STATUS_DOWN = false;

    public int        $domainPingId = 0;
    public int        $domainId     = 0;
    public bool       $status       = false;
    public ?string    $siteName     = null;
    public ?\DateTime $siteTime     = null;
    public ?string    $timezone     = null;
    public ?int       $bytes        = null;
    public \DateTime  $created;


    public function __construct()
    {
        $this->created = new \DateTime();
    }

    public static function create(int $domainId, bool $status, array $data = []): self
    {
        $ping = new DomainPing();
        $ping->domainId  = $domainId;
        $ping->status    = $status;
        $ping->siteName  = $data['siteName'] ?? null;
        $ping->timezone  = $data['timezone'] ?? null;
        if (isset($data['timestamp'])) {
            $ping->siteTime = new \DateTime('@'.$data['timestamp'], new \DateTimeZone($data['timezone'] ?? 'UTC'));
        }
        $ping->bytes     = $data['bytes'] ?? null;
        $ping->save();
        return $ping;
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->domainPingId) {
            $values['domain_ping_id'] = $this->domainPingId;
            Db::update('domain_ping', 'domain_ping_id', $values);
        } else {
            unset($values['domain_ping_id']);
            Db::insert('domain_ping', $values);
            $this->domainPingId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public static function find(int $domainPingId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM domain_ping
            WHERE domain_ping_id = :domainPingId",
            compact('domainPingId'),
            self::class
        );
    }

    /**
     * @return array<int,DomainPing>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM domain_ping",
            [],
            self::class
        );
    }

    /**
     * @return array<int,DomainPing>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w = '';
            $w .= 'LOWER(a.site_name) LIKE LOWER(:search) OR ';
            $w .= 'a.domain_ping_id = :search OR ';
            if (is_numeric($filter['search'])) {
                $w .= 'a.domain_ping_id = :search OR ';
            }
            $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['always'])) {
            if (!is_array($filter['always'])) $filter['always'] = [$filter['always']];
            $filter->appendWhere('(a.domain_ping_id IN :always) OR ', $filter['always']);
        }

        if (!empty($filter['id'])) {
            $filter['domainPingId'] = $filter['id'];
        }
        if (!empty($filter['domainPingId'])) {
            if (!is_array($filter['domainPingId'])) $filter['domainPingId'] = [$filter['domainPingId']];
            $filter->appendWhere('a.domain_ping_id IN :domainPingId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.domain_ping_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['domainId'])) {
            $filter->appendWhere('a.domain_id = :domainId AND ');
        }

        if (is_bool(truefalse($filter['status'] ?? null))) {
            $filter['status'] = truefalse($filter['status']);
            $filter->appendWhere('a.status = :status AND ');
        }

        if (!empty($filter['timezone'])) {
            $filter->appendWhere('a.timezone = :timezone AND ');
        }

        return Db::query("
            SELECT *
            FROM domain_ping a
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->domainPingId) {
            $errors['domainPingId'] = 'Invalid value: domainPingId';
        }

        if (!$this->domainId) {
            $errors['domainId'] = 'Invalid value: domainId';
        }

        return $errors;
    }

}