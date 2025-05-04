<?php
namespace App\Db;

use App\Db\Traits\UserTrait;
use Bs\Traits\ForeignModelTrait;
use Tk\DataMap\DataMap;
use Tk\DataMap\Db\Json;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Exception;

class StatusLog extends Model
{
    use UserTrait;
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

    public static function create(Model $model, string $message = '', bool $notify = true): self
    {
        $obj = new self();
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
        if (!($prev instanceof self) || $obj->name != $prev->name) {
            $obj->save();
            $model->onStatusChanged($obj);
        }

        return $obj;
    }

    public function getPrevious(): ?self
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
        return $this->getPrevious()->name ?? '';
    }

    public function getLabel(): string
    {
        return ucwords(preg_replace('/[A-Z]/', ' $0', (string)\Tk\ObjectUtil::basename($this->fkey)));
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
        $filter->appendFrom('status_log a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.name) LIKE LOWER(:lSearch)';
            $w .= 'OR LOWER(a.message) LIKE LOWER(:lSearch)';
            $w .= 'OR a.status_log_id = :search';
            if ($w) $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['statusLogId'] = $filter['id'];
        }
        if (!empty($filter['statusLogId'])) {
            if (!is_array($filter['statusLogId'])) $filter['statusLogId'] = [$filter['statusLogId']];
            $filter->appendWhere('AND a.status_log_id IN :statusLogId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.status_log_id NOT IN :exclude', $filter['exclude']);
        }

        if (!empty($filter['userId'])) {
            $filter->appendWhere('AND a.user_id = :userId');
        }

        if (!empty($filter['model']) && $filter['model'] instanceof Model) {
            $filter['fid'] = $filter['model']->getId();
            $filter['fkey'] = get_class($filter['model']);
        }
        if (!empty($filter['fkey'])) {
            $filter->appendWhere('AND a.fkey = :fkey');
        }
        if (!empty($filter['fid'])) {
            $filter->appendWhere('AND a.fid = :fid');
        }

        if (!empty($filter['name'])) {
            $filter->appendWhere('AND a.name = :name');
        }
        if (is_bool(truefalse($filter['notify'] ?? null))) {
            $filter['notify'] = truefalse($filter['notify']);
            $filter->appendWhere('AND a.notify = :notify');
        }

        if (!empty($filter['before']) && $filter['before'] instanceof \DateTime) {
            $filter['before'] = $filter['before']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('AND a.created < :before');
        }
        if (!empty($filter['after']) && $filter['after'] instanceof \DateTime) {
            $filter['after'] = $filter['after']->format(\Tk\Date::FORMAT_ISO_DATETIME);
            $filter->appendWhere('AND a.created > :after');
        }

        if (!empty($filter['monthFrom']) && $filter['monthFrom'] instanceof \DateTime) {
            $filter['monthFrom'] = $filter['monthFrom']->format('Y-m');
            $filter->appendWhere('AND DATE_FORMAT(a.created, "%%Y-%%m") >= :monthFrom');
        }
        if (!empty($filter['monthTo']) && $filter['monthTo'] instanceof \DateTime) {
            $filter['monthTo'] = $filter['monthTo']->format('Y-m');
            $filter->appendWhere('AND DATE_FORMAT(a.created, "%%Y-%%m") <= :monthTo');
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