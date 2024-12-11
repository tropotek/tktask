<?php
namespace App\Db;

use App\Db\Traits\CompanyTrait;
use App\Db\Traits\UserTrait;
use Bs\Traits\TimestampTrait;
use DateTime;
use Tk\DataMap\DataMap;
use Tk\Date;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Money;

class Project extends Model implements StatusInterface
{
    use TimestampTrait;
    use UserTrait;
    use CompanyTrait;

    const string STATUS_PENDING    = 'pending';     // Project waiting for start date ?
    const string STATUS_ACTIVE     = 'active';      // currently in progress
    const string STATUS_HOLD       = 'hold';
    const string STATUS_COMPLETED  = 'completed';
    const string STATUS_CANCELLED  = 'cancelled';

    const array STATUS_OPEN = [
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_HOLD,
    ];

    const array STATUS_LIST = [
        self::STATUS_PENDING   => 'Pending',
        self::STATUS_ACTIVE    => 'Active',
        self::STATUS_HOLD      => 'Hold',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    const array STATUS_CSS = [
        self::STATUS_PENDING   => 'primary',
        self::STATUS_ACTIVE    => 'success',
        self::STATUS_HOLD      => 'secondary',
        self::STATUS_COMPLETED => 'info',
        self::STATUS_CANCELLED => 'danger',
    ];

    public int       $projectId   = 0;
    public int       $userId      = 0;
    public int       $companyId   = 0;
    public string    $status      = self::STATUS_PENDING;
    public string    $name        = '';
    public Money     $quote;
    public ?DateTime $startOn     = null;
    public ?DateTime $endOn       = null;
    public string    $description = '';
    public string    $notes       = '';
    public DateTime  $modified;
    public DateTime  $created;


    public function __construct()
    {
        $this->modified  = new DateTime();
        $this->created   = new DateTime();
        $this->quote     = new Money();
        $this->startOn   = Date::floor(new DateTime());
        $this->endOn     = Date::floor(Date::create()->add(new \DateInterval('P3M')));
    }

    public static function getDataMap(): DataMap
    {
        $map = parent::getDataMap();
        $map->addType(new \Tk\DataMap\Db\Date('start_on', 'startOn'));
        $map->addType(new \Tk\DataMap\Db\Date('end_on', 'endOn'));
        return $map;
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->projectId) {
            $values['project_id'] = $this->projectId;
            Db::update('project', 'project_id', $values);
        } else {
            unset($values['project_id']);
            Db::insert('project', $values);
            $this->projectId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public function isEditable(): bool
    {
        return !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public static function find(int $projectId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM project
            WHERE project_id = :projectId",
            compact('projectId'),
            self::class
        );
    }

    /**
     * @return array<int,Project>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM project",
            [],
            self::class
        );
    }

    /**
     * @return array<int,Project>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.name) LIKE LOWER(:search) OR ';
            $w  = 'LOWER(a.description) LIKE LOWER(:search) OR ';
            $w .= 'a.project_id = :search OR ';
            if (is_numeric($filter['search'])) {
                $w .= 'a.project_id = :search OR ';
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['projectId'] = $filter['id'];
        }
        if (!empty($filter['projectId'])) {
            if (!is_array($filter['projectId'])) $filter['projectId'] = [$filter['projectId']];
            $filter->appendWhere('a.project_id IN :projectId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.project_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['userId'])) {
            $filter->appendWhere('a.user_id = :userId AND ');
        }
        if (!empty($filter['companyId'])) {
            $filter->appendWhere('a.company_id = :companyId AND ');
        }
        if (!empty($filter['status'])) {
            if (!is_array($filter['status'])) $filter['status'] = [$filter['status']];
            $filter->appendWhere('a.status IN :status AND ');
        }
        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = :name AND ');
        }
        if (!empty($filter['quote'])) {
            $filter->appendWhere('a.quote = :quote AND ');
        }

        return Db::query("
            SELECT *
            FROM project a
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->projectId) {
            $errors['projectId'] = 'Invalid value: projectId';
        }

        if (!$this->userId) {
            $errors['userId'] = 'Invalid value: userId';
        }

        if (!$this->companyId) {
            $errors['companyId'] = 'Invalid value: companyId';
        }

        if (!$this->name) {
            $errors['name'] = 'Invalid value: name';
        }

        if (!$this->status) {
            $errors['status'] = 'Invalid value: status';
        }

        if ($this->startOn > $this->endOn) {
            $errors['endOn'] = 'Start date must be before end date.';
        }

        return $errors;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function onStatusChanged(StatusLog $statusLog): void { }
}