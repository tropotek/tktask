<?php
namespace App\Db;

use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;

/**
 * Stored the information from a domain ping.
 *
 * NOTE: Domain pings are stored for a year then cleared.
 *       See DB events.sql `evt_delete_domain_ping`
 */
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
    public ?\DateTime $created      = null;


    public function __construct()
    {

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

    /**
     * @return array<int,DomainPing>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);
        $filter->appendFrom('domain_ping a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.site_name) LIKE LOWER(:lSearch)';
            $w .= 'OR a.domain_ping_id = :search';
            $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['domainPingId'] = $filter['id'];
        }
        if (!empty($filter['domainPingId'])) {
            if (!is_array($filter['domainPingId'])) $filter['domainPingId'] = [$filter['domainPingId']];
            $filter->appendWhere('AND a.domain_ping_id IN :domainPingId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.domain_ping_id NOT IN :exclude', $filter['exclude']);
        }

        if (!empty($filter['domainId'])) {
            $filter->appendWhere('AND a.domain_id = :domainId');
        }

        if (is_bool(truefalse($filter['status'] ?? null))) {
            $filter['status'] = truefalse($filter['status']);
            $filter->appendWhere('AND a.status = :status');
        }

        if (!empty($filter['timezone'])) {
            $filter->appendWhere('AND a.timezone = :timezone');
        }

        if (!empty($filter['always'])) {
            if (!is_array($filter['always'])) $filter['always'] = [$filter['always']];
            $filter->appendWhere('OR (a.domain_ping_id IN :always)', $filter['always']);
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

        if (!$this->domainId) {
            $errors['domainId'] = 'Invalid value: domainId';
        }

        return $errors;
    }

}