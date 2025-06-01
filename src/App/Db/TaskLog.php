<?php
namespace App\Db;

use App\Db\Traits\ProductTrait;
use App\Db\Traits\TaskTrait;
use App\Db\Traits\UserTrait;
use App\Form\DataMap\Minutes;
use Tk\Config;
use Tk\DataMap\DataMap;
use Tk\DataMap\Form\Boolean;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Exception;

class TaskLog extends Model
{
    use TaskTrait;
    use UserTrait;
    use ProductTrait;

    const int DEFAULT_PRODUCT_ID = 1;

    public int       $taskLogId     = 0;
    public int       $taskId        = 0;
    public int       $userId        = 0;
    public int       $productId     = self::DEFAULT_PRODUCT_ID;
    public bool      $billable      = true;
    public \DateTime $startAt;
    public int       $minutes       = 0;
    public string    $status        = '';   // task status
    public string    $comment       = '';
    public string    $dataPath      = '';
    public \DateTime $modified;
    public \DateTime $created;


    public function __construct()
    {
        $this->startAt  = new \DateTime();
        $this->modified = new \DateTime();
        $this->created  = new \DateTime();

        $config = Config::instance();
        if (User::getAuthUser() instanceof User) {
            $this->userId = User::getAuthUser()->userId;
        }

        if (is_bool(truefalse($config->get('site.taskLog.billable.default', true)))) {
            $this->billable = truefalse($config->get('site.taskLog.billable.default', true));
        }
    }

    public static function getFormMap(): DataMap
    {
        $map = parent::getFormMap();
        $map->addType(new Boolean('billable'));
        $map->addType(new Minutes('minutes'));
        return $map;
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->taskLogId) {
            $values['task_log_id'] = $this->taskLogId;
            Db::update('task_log', 'task_log_id', $values);
        } else {
            unset($values['task_log_id']);
            Db::insert('task_log', $values);
            $this->taskLogId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public function delete(): bool
    {
        if (!$this->getTask()->isOpen()) {
            throw new Exception("failed to delete log id {$this->taskLogId} from closed task id {$this->taskId}");
        }
        $ok = Db::delete('task_log', ['task_id' => $this->taskId]);
        return ($ok !== false);
    }

    public static function find(int $taskLogId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM v_task_log
            WHERE task_log_id = :taskLogId",
            compact('taskLogId'),
            self::class
        );
    }

    /**
     * @return array<int,TaskLog>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM v_task_log",
            [],
            self::class
        );
    }

    /**
     * @return array<int,TaskLog>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);
        $filter->appendFrom('v_task_log a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.comment) LIKE LOWER(:lSearch)';
            $w .= 'OR a.task_log_id = :search';
            $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['taskLogId'] = $filter['id'];
        }
        if (!empty($filter['taskLogId'])) {
            if (!is_array($filter['taskLogId'])) $filter['taskLogId'] = [$filter['taskLogId']];
            $filter->appendWhere('AND a.task_log_id IN :taskLogId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.task_log_id NOT IN :exclude', $filter['exclude']);
        }

        if (!empty($filter['taskId'])) {
            $filter->appendWhere('AND a.task_id = :taskId');
        }

        if (!empty($filter['userId'])) {
            if (!is_array($filter['userId'])) $filter['userId'] = [$filter['userId']];
            $filter->appendWhere('AND a.user_id IN :userId');
        }

        if (!empty($filter['productId'])) {
            if (!is_array($filter['productId'])) $filter['productId'] = [$filter['productId']];
            $filter->appendWhere('AND a.product_id IN :productId');
        }

        if (!empty($filter['status'])) {
            if (!is_array($filter['status'])) $filter['status'] = [$filter['status']];
            $filter->appendWhere('AND a.status IN :status');
        }

        if (is_bool(truefalse($filter['billable'] ?? null))) {
            $filter['billable'] = truefalse($filter['billable']);
            $filter->appendWhere('AND a.billable = :billable');
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

        if (!$this->taskId) {
            $errors['taskId'] = 'Invalid value: taskId';
        }

        if (!$this->userId) {
            $errors['userId'] = 'Invalid value: userId';
        }

        if ($this->billable && !$this->minutes) {
            $errors['minutes'] = 'Billable logs must contain a task duration';
        }

        return $errors;
    }

}