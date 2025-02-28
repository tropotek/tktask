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
    public string    $status        = Task::STATUS_PENDING;
    public bool      $billable      = true;
    public \DateTime $startAt;
    public int       $minutes       = 0;
    public string    $comment       = '';
    public string    $notes         = '';
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
        if ($this->getTask()->isOpen()) {
            throw new Exception("failed to delete log id {$this->taskLogId} from closed task id {$this->taskId}");
        }
        $ok = Db::delete('task_log', ['task_id' => $this->taskId]);
        $this->getTask()->resetStatus();
        return ($ok !== false);
    }

    public static function create(?Task $task = null, ?Product $product = null): static
    {
        $obj = new self();
        if ($task) {
            $obj->taskId = $task->taskId;
            $obj->status = $task->status;
        }
        if ($product) {
            $obj->productId = $product->productId;
        }
        return $obj;
    }

    public static function find(int $taskLogId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM task_log
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
            FROM task_log",
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

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w = '';
            $w .= 'LOWER(a.comment) LIKE LOWER(:search) OR ';
            $w .= 'a.task_log_id = :search OR ';
            if (is_numeric($filter['search'])) {
                $w .= 'a.task_log_id = :search OR ';
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['taskLogId'] = $filter['id'];
        }
        if (!empty($filter['taskLogId'])) {
            if (!is_array($filter['taskLogId'])) $filter['taskLogId'] = [$filter['taskLogId']];
            $filter->appendWhere('a.task_log_id IN :taskLogId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.task_log_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['taskId'])) {
            $filter->appendWhere('a.task_id = :taskId AND ');
        }

        if (!empty($filter['userId'])) {
            if (!is_array($filter['userId'])) $filter['userId'] = [$filter['userId']];
            $filter->appendWhere('a.user_id IN :userId AND ');
        }

        if (!empty($filter['productId'])) {
            if (!is_array($filter['productId'])) $filter['productId'] = [$filter['productId']];
            $filter->appendWhere('a.product_id IN :productId AND ');
        }

        if (!empty($filter['status'])) {
            if (!is_array($filter['status'])) $filter['status'] = [$filter['status']];
            $filter->appendWhere('a.status IN :status AND ');
        }

        if (is_bool(truefalse($filter['billable'] ?? null))) {
            $filter['billable'] = truefalse($filter['billable']);
            $filter->appendWhere('a.billable = :billable AND ');
        }

        return Db::query("
            SELECT *
            FROM task_log a
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->taskLogId) {
            $errors['taskLogId'] = 'Invalid value: taskLogId';
        }

        if (!$this->taskId) {
            $errors['taskId'] = 'Invalid value: taskId';
        }

        if (!$this->userId) {
            $errors['userId'] = 'Invalid value: userId';
        }

        if (!$this->productId) {
            $errors['productId'] = 'Invalid value: productId';
        }

        if (!$this->status) {
            $errors['status'] = 'Invalid value: status';
        }

        if ($this->billable && !$this->minutes) {
            $errors['minutes'] = 'Billable logs must contain a task duration';
        }

        return $errors;
    }

}