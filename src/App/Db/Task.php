<?php
namespace App\Db;

use App\Db\Traits\CompanyTrait;
use App\Db\Traits\ProjectTrait;
use App\Db\Traits\TaskCategoryTrait;
use App\Form\DataMap\Minutes;
use Bs\Registry;
use Bs\Traits\TimestampTrait;
use Tk\DataMap\DataMap;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Exception;
use Tk\Money;

class Task extends Model implements StatusInterface
{
    use TimestampTrait;
    use CompanyTrait;
    use ProjectTrait;
    use TaskCategoryTrait;

    const int CATEGORY_DEFAULT = 1;

    const string STATUS_PENDING    = 'pending';   // Project waiting for start date ?
    const string STATUS_HOLD       = 'hold';      // Task on hold awaiting info from client to proceed
    const string STATUS_OPEN       = 'open';      // Task is open/active and a work in progress
    const string STATUS_CLOSED     = 'closed';    // All worked checked and the system can now invoice for time worked
    const string STATUS_CANCELLED  = 'cancelled'; // Task cancelled and nothing billed.

    const array STATUS_LIST = [
        self::STATUS_PENDING   => 'Pending',
        self::STATUS_HOLD      => 'Hold',
        self::STATUS_OPEN      => 'Open',
        self::STATUS_CLOSED    => 'Closed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    const array STATUS_CSS = [
        self::STATUS_PENDING   => 'primary',
        self::STATUS_OPEN      => 'success',
        self::STATUS_HOLD      => 'secondary',
        self::STATUS_CLOSED    => 'warning',
        self::STATUS_CANCELLED => 'danger',
    ];

    const int PRIORITY_NONE = 0;
    const int PRIORITY_LOW  = 1;
    const int PRIORITY_MED  = 5;
    const int PRIORITY_HIGH = 10;

    const array PRIORITY_LIST = [
        self::PRIORITY_NONE => 'None',
        self::PRIORITY_LOW  => 'Low',
        self::PRIORITY_MED  => 'Medium',
        self::PRIORITY_HIGH => 'High',
    ];

    const array PRIORITY_CSS = [
        self::PRIORITY_NONE => 'secondary',
        self::PRIORITY_LOW  => 'success',
        self::PRIORITY_MED  => 'warning',
        self::PRIORITY_HIGH => 'danger',
    ];

    public int        $taskId         = 0;
    public int        $companyId      = 0;
    public ?int       $projectId      = null;
    public int        $taskCategoryId = self::CATEGORY_DEFAULT;
    public int        $creatorUserId  = 0;
    public int        $assignedUserId = 0;
    public ?int       $closedUserId   = null;
    public string     $status         = self::STATUS_PENDING;
    public string     $subject        = '';
    public string     $comments       = '';
    public int        $priority       = self::PRIORITY_MED;
    public int        $minutes        = 0;
    public ?\DateTime $invoiced       = null;       // TODO if not used anywhere, Remove ???
    public \DateTime  $modified;
    public \DateTime  $created;

    private ?User $_creator      = null;
    private ?User $_assignedUser = null;
    private ?User $_closedUser   = null;

    public function __construct()
    {
        $this->modified = new \DateTime();
        $this->created = new \DateTime();
        $user = User::getAuthUser();
        if ($user instanceof User) {
            $this->creatorUserId = $user->userId;
            $this->assignedUserId = $user->userId;
        }
    }

    public static function getFormMap(): DataMap
    {
        $map = parent::getFormMap();
        $map->addType(new Minutes('minutes'));
        return $map;
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->taskId) {
            $values['task_id'] = $this->taskId;
            Db::update('task', 'task_id', $values);
        } else {
            unset($values['task_id']);
            Db::insert('task', $values);
            $this->taskId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public function isOpen(): bool
    {
        return !in_array($this->getStatus(), [self::STATUS_CLOSED, self::STATUS_CANCELLED]);
    }

    public function isEditable(): bool
    {
        if ($this->getProject() && !$this->getProject()->isEditable()) return false;
        return $this->isOpen();
    }

    public function getDataPath(): string
    {
        if (!$this->taskId) throw new Exception("object without task_id");
        return sprintf('/task/%s/%s', $this->getCreated('Y'), $this->taskId);
    }

    public function getCreator(): ?User
    {
        if (is_null($this->_creator)) $this->_creator = User::find($this->creatorUserId);
        return $this->_creator;
    }

    public function getAssignedUser(): ?User
    {
        if (is_null($this->_assignedUser)) $this->_assignedUser = User::find($this->assignedUserId);
        return $this->_assignedUser;
    }

    public function getClosedUser(): ?User
    {
        if (is_null($this->_closedUser)) $this->_closedUser = User::find($this->closedUserId);
        return $this->_closedUser;
    }

    /**
     * Called after deleting a task log to reset the last most recent task status
     */
    public function resetStatus(): static
    {
        $log = TaskLog::findFiltered(Filter::create(['taskId' => $this->getId()], '-created'))[0] ?? null;
        $this->status = self::STATUS_PENDING;
        if ($log instanceof TaskLog) {
            $this->status = $log->status;
        }
        $this->save();
        return $this;
    }

    public function reopen(): static
    {
        if ($this->isEditable()) {
            return $this;
        }

        $log = TaskLog::create($this);
        $log->billable = false;
        $log->status = self::STATUS_OPEN;
        $log->comment = '-- Task Re-Opened. --';
        $this->status = self::STATUS_OPEN;
        $this->addTaskLog($log, $log->comment);

        return $this;
    }

    public function addTaskLog(TaskLog $log, string $statusMsg = '', bool $statusNotify = true): static
    {
        if (!$this->isEditable()) {
            throw new \Tk\Exception('Cannot add a TaskLog with Task status of: ' . $this->getStatus());
        }

        $log->taskId = $this->taskId;
        $this->status = $log->status;
        $log->save();
        $this->save();

        \App\Db\StatusLog::create($this, $statusMsg, $statusNotify);

        return $this;
    }

    /**
     * @return array<int,TaskLog>
     */
    public function getLogList(array|Filter $filter = []): array
    {
        $filter = Filter::create($filter, '-created');
        $filter->set('taskId', $this->taskId);
        return TaskLog::findFiltered($filter);
    }

    public function getCompletedTime(): int
    {
        $time = 0;
        foreach ($this->getLogList() as $task) {
            $time += $task->minutes;
        }
        return $time;
    }

    public function getTotalBillableTime(): int
    {
        $time = 0;
        foreach ($this->getLogList(['billable' => true]) as $task) {
            $time += $task->minutes;
        }
        return $time;
    }

    public function getCost(): Money
    {
        // billable TaskLogs
        $logs = TaskLog::findFiltered(array(
            'taskId' => $this->taskId,
            'billable' => true
        ));
        $total = Money::create();
        foreach ($logs as $log) {
            $total = $total->add($log->getProduct()->price->multiply(round($log->minutes/60, 2)));
        }
        return $total;
    }

    public function getEstimatedCost(): Money
    {
        $price = Product::getDefaultLaborProduct()->price->multiply(round($this->minutes/60, 2));
        return \Tk\Money::create($price);
    }

    public function createInvoiceItem(): InvoiceItem
    {
        $total = $this->getCost();
        $subject = sprintf('%s (%s hrs)', $this->subject, round($this->getTotalBillableTime()/60, 2));
        return \App\Db\InvoiceItem::create('TSK-'.$this->getId(), $subject, $total);
    }

    public static function find(int $taskId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM task
            WHERE task_id = :taskId",
            compact('taskId'),
            self::class
        );
    }

    /**
     * @return array<int,Task>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM task",
            [],
            self::class
        );
    }

    /**
     * @return array<int,Task>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.subject) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.comments) LIKE LOWER(:search) OR ';
            $w .= 'a.task_id = :search OR ';
            if (is_numeric($filter['search'])) {
                $w .= 'a.task_id = :search OR ';
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['taskId'] = $filter['id'];
        }
        if (!empty($filter['taskId'])) {
            if (!is_array($filter['taskId'])) $filter['taskId'] = [$filter['taskId']];
            $filter->appendWhere('a.task_id IN :taskId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.task_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['companyId'])) {
            $filter->appendWhere('a.company_id = :companyId AND ');
        }

        if (!empty($filter['projectId'])) {
            $filter->appendWhere('a.project_id = :projectId AND ');
        }

        if (!empty($filter['taskCategoryId'])) {
            if (!is_array($filter['taskCategoryId'])) $filter['taskCategoryId'] = [$filter['taskCategoryId']];
            $filter->appendWhere('a.task_category_id IN :taskCategoryId AND ');
        }

        if (!empty($filter['creatorUserId'])) {
            if (!is_array($filter['creatorUserId'])) $filter['creatorUserId'] = [$filter['creatorUserId']];
            $filter->appendWhere('a.creator_user_id IN :creatorUserId AND ');
        }

        if (!empty($filter['assignedUserId'])) {
            if (!is_array($filter['assignedUserId'])) $filter['assignedUserId'] = [$filter['assignedUserId']];
            $filter->appendWhere('a.assigned_user_id IN :assignedUserId AND ');
        }

        if (!empty($filter['closedUserId'])) {
            if (!is_array($filter['closedUserId'])) $filter['closedUserId'] = [$filter['closedUserId']];
            $filter->appendWhere('a.closed_user_id IN :closedUserId AND ');
        }

        if (!empty($filter['status'])) {
            if (!is_array($filter['status'])) $filter['status'] = [$filter['status']];
            $filter->appendWhere('a.status IN :status AND ');
        }

        if (!empty($filter['priority'])) {
            if (!is_array($filter['priority'])) $filter['priority'] = [$filter['priority']];
            $filter->appendWhere('a.priority IN :priority AND ');
        }

        return Db::query("
            SELECT *
            FROM task a
            {$filter->getSql()}",
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

        if (!$this->companyId) {
            $errors['companyId'] = 'Invalid value: companyId';
        }

        if (!$this->taskCategoryId) {
            $errors['taskCategoryId'] = 'Invalid value: taskCategoryId';
        }

        if (!$this->creatorUserId) {
            $errors['creatorUserId'] = 'Invalid value: creatorUserId';
        }

        if (!$this->assignedUserId) {
            $errors['assignedUserId'] = 'Invalid value: assignedUserId';
        }

        if ($this->getProject()) {
            if ($this->assignedUserId) {
                // TODO: complete with project member functions ...
//                if (!$this->getProject()->isMember($this->getAssignedUser())) {
//                    $errors['projectId'] = 'Invalid value: Assigned User is not a member of this project.';
//                }
            }
            if ($this->companyId && $this->getProject()->companyId != $this->companyId) {
                $errors['projectId'] = 'Invalid value: Selected company does not belong to this project';
            }
        }

        if (!$this->subject) {
            $errors['subject'] = 'Invalid value: subject';
        }

        if (!$this->status) {
            $errors['status'] = 'Invalid value: status';
        }

        if (!$this->priority) {
            $errors['priority'] = 'Invalid value: priority';
        }

        return $errors;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function onStatusChanged(StatusLog $statusLog): void
    {
        $prevStatusName = $statusLog->getPreviousName();
        switch($statusLog->name) {
            case self::STATUS_CLOSED:
                if ($prevStatusName != self::STATUS_CANCELLED) {
                    // Add task to open invoice
                    if ($this->getTotalBillableTime() <= 0) break;
                    if (is_null($this->getCompany()) || !Registry::instance()->get('site.invoice.enable', false)) break;
                    $invoice = \App\Db\Invoice::getOpenInvoice($this->getCompany());
                    if ($invoice && $invoice->getStatus() == \App\Db\Invoice::STATUS_OPEN) {
                        $item = $this->createInvoiceItem();
                        $invoice->addItem($item);
                    }
                }
                break;
        }
    }
}