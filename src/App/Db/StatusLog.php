<?php
namespace App\Db;

use App\Db\Traits\UserTrait;
use Bs\Traits\ForeignModelTrait;
use Bs\Traits\TimestampTrait;
use Tk\DataMap\DataMap;
use Tk\DataMap\Db\Json;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Exception;

class StatusLog extends Model
{
    use UserTrait;
    use TimestampTrait;
    use ForeignModelTrait;

    public int        $statusLogId = 0;
    public ?int       $userId      = null;
    public string     $fkey        = '';
    public int        $fid         = 0;
    public string     $name        = '';
    public bool       $notify      = true;
    public ?string    $message     = null;
    public ?\stdClass $data        = null;
    public \DateTime  $created;

    private ?StatusLog $_previous = null;


    public function __construct()
    {
        $this->created = new \DateTime();
    }

    public static function getDataMap(): DataMap
    {
        $map = parent::getDataMap();
        $map->addType(new Json('data'));
        return $map;
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->statusLogId) {
            $values['status_log_id'] = $this->statusLogId;
            Db::update('status_log', 'status_log_id', $values);
        } else {
            unset($values['status_log_id']);
            Db::insert('status_log', $values);
            $this->statusLogId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public static function create(Model $model, string $message = '', bool $notify = true): static
    {
        $obj = new static();
        $obj->setDbModel($model);

        if (!($model instanceof StatusInterface)) {
            throw new Exception("object does not implement \App\Db\StatusInterface");
        }

        $user = User::getAuthUser();
        if ($user instanceof User) {
            $obj->userId = $user->userId;
        }

        $obj->name = $model->getStatus();
        $obj->message = trim($message);
        $obj->notify = $notify;

        // save log if status has changed
        $prev = $obj->getPrevious();
        if (!($prev instanceof StatusLog) || $obj->name != $prev->name) {
            $obj->save();
            $model->onStatusChanged($obj);
        }

        return $obj;
    }

    public function getPrevious(): ?static
    {
        if (!$this->_previous) {
            $filter = array(
                'before' => $this->created,
                'fid' => $this->fid,
                'fkey' => $this->fkey
            );
            $this->_previous = self::findFiltered(Filter::create($filter, '-created'))[0] ?? null;
        }
        return $this->_previous;
    }

    public function getPreviousName(): string
    {
        return $this->getPrevious()?->name ?? '';
    }

    public function getLabel(): string
    {
        return ucwords(preg_replace('/[A-Z]/', ' $0', \Tk\ObjectUtil::basename($this->fkey)));
    }

    public static function find(int $statusLogId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM status_log
            WHERE status_log_id = :statusLogId",
            compact('statusLogId'),
            self::class
        );
    }

    /**
     * @return array<int,StatusLog>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM status_log",
            [],
            self::class
        );
    }

    /**
     * @return array<int,StatusLog>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w = '';
            $w .= 'LOWER(a.name) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.message) LIKE LOWER(:search) OR ';
            $w .= 'a.status_log_id = :search OR ';
            if (is_numeric($filter['search'])) {
                $w .= 'a.status_log_id = :search OR ';
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['statusLogId'] = $filter['id'];
        }
        if (!empty($filter['statusLogId'])) {
            if (!is_array($filter['statusLogId'])) $filter['statusLogId'] = [$filter['statusLogId']];
            $filter->appendWhere('a.status_log_id IN :statusLogId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.status_log_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['userId'])) {
            $filter->appendWhere('a.user_id = :userId AND ');
        }

        if (!empty($filter['model']) && $filter['model'] instanceof Model) {
            $filter['fid'] = $filter['model']->getId();
            $filter['fkey'] = get_class($filter['model']);
        }
        if (!empty($filter['fkey'])) {
            $filter->appendWhere('a.fkey = :fkey AND ');
        }
        if (!empty($filter['fid'])) {
            $filter->appendWhere('a.fid = :fid AND ');
        }

        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = :name AND ');
        }
        if (is_bool(truefalse($filter['notify'] ?? null))) {
            $filter['notify'] = truefalse($filter['notify']);
            $filter->appendWhere('a.notify = :notify AND ');
        }

        if (!empty($filter['before']) && $filter['before'] instanceof \DateTime) {
            $filter['before'] = $filter['before']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('a.created < :before AND ');
        }
        if (!empty($filter['after']) && $filter['after'] instanceof \DateTime) {
            $filter['after'] = $filter['after']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('a.created > :after AND ');
        }

        if (!empty($filter['monthFrom']) && $filter['monthFrom'] instanceof \DateTime) {
            $filter['monthFrom'] = $filter['monthFrom']->format('Y-m');
            $filter->appendWhere('DATE_FORMAT(a.created, "%%Y-%%m") >= :monthFrom AND ');
        }
        if (!empty($filter['monthTo']) && $filter['monthTo'] instanceof \DateTime) {
            $filter['monthTo'] = $filter['monthTo']->format('Y-m');
            $filter->appendWhere('DATE_FORMAT(a.created, "%%Y-%%m") <= :monthTo AND ');
        }

        return Db::query("
            SELECT *
            FROM status_log a
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->statusLogId) {
            $errors['statusLogId'] = 'Invalid value: statusLogId';
        }

        if (!$this->fkey) {
            $errors['fkey'] = 'Invalid value: fkey';
        }

        if (!$this->fid) {
            $errors['fid'] = 'Invalid value: fid';
        }

        if (!$this->name) {
            $errors['name'] = 'Invalid value: name';
        }

        return $errors;
    }

}