<?php
namespace App\Db;

use App\Db\Traits\CompanyTrait;
use App\Db\Traits\ProjectTrait;
use App\Db\Traits\TaskCategoryTrait;
use App\Form\DataMap\Minutes;
use Bs\Registry;
use Tk\DataMap\DataMap;
use Tk\Db\Model;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Exception;
use Tk\Money;

class Task extends Model
{
    use CompanyTrait;
    use ProjectTrait;
    use TaskCategoryTrait;

    const int CATEGORY_DEFAULT = 1;

//    const string STATUS_PENDING    = 'pending';   //
//    const string STATUS_HOLD       = 'hold';      // Task on hold awaiting info from client to proceed
    const string STATUS_OPEN       = 'open';      // Task is open/active and a work in progress
    const string STATUS_CLOSED     = 'closed';    // All worked checked and the system can now invoice for time worked
    const string STATUS_CANCELLED  = 'cancelled'; // Task cancelled and nothing billed.

    const array STATUS_LIST = [
//        self::STATUS_PENDING   => 'Pending',
//        self::STATUS_HOLD      => 'Hold',
        self::STATUS_OPEN      => 'Open',
        self::STATUS_CLOSED    => 'Closed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    const array STATUS_CSS = [
        //self::STATUS_PENDING   => 'primary',
        self::STATUS_OPEN      => 'success',
        //self::STATUS_HOLD      => 'secondary',
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
    public int        $assignedUserId = 0;
    public string     $subject        = '';
    public string     $comments       = '';
    public int        $priority       = self::PRIORITY_MED;
    public int        $minutes        = 0;
    public ?\DateTime $closedAt       = null;
    public ?\DateTime $cancelledAt    = null;
    public ?\DateTime $invoicedAt     = null;
    public ?int       $invoiceItemId  = null;
    public ?\DateTime $modified       = null;
    public ?\DateTime $created        = null;

    public string     $dataPath       = '';
    public string     $status         = '';
    public string     $companyName    = '';
    public string     $assignedName   = '';

    private ?User $_assignedUser = null;

    public function __construct()
    {
        $user = User::getAuthUser();
        if ($user instanceof User) {
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
        return !in_array($this->status, [self::STATUS_CLOSED, self::STATUS_CANCELLED]);
    }

    public function isEditable(): bool
    {
        if ($this->getProject() && !$this->getProject()->isEditable()) return false;
        return $this->isOpen();
    }

    public function getAssignedUser(): ?User
    {
        if (is_null($this->_assignedUser)) $this->_assignedUser = User::find($this->assignedUserId);
        return $this->_assignedUser;
    }

    public function close(bool $invoice = false): static
    {
        $this->closedAt = new \DateTime();
        $this->cancelledAt = null;
        $this->save();

        if ($invoice && Registry::getValue('site.invoice.enable', false)) {
            if ($this->getTotalBillableTime() <= 0) return $this;
            $company = $this->getCompany();
            if (is_null($company)) return $this;
            $invoice = \App\Db\Invoice::getOpenInvoice($this->getCompany());
            $item = $this->createInvoiceItem();
            $invoice->addItem($item);
            $this->invoicedAt = new \DateTime();
            $this->invoiceItemId = $item->invoiceItemId;
            $this->save();
        }

        return $this;
    }

    public function cancel(): static
    {
        $this->closedAt = null;
        $this->cancelledAt = new \DateTime();
        $this->save();
        return $this;
    }

    public function reopen(): static
    {
        if ($this->isEditable()) {
            return $this;
        }

        $this->cancelledAt = null;
        $this->closedAt = null;
        $this->save();

        $log = new TaskLog();
        $log->taskId = $this->taskId;
        $log->billable = false;
        $log->comment = '-- Task Re-Opened. --';
        $log->save();

        return $this;
    }

    public function addTaskLog(TaskLog $log): static
    {
        if (!$this->isEditable()) {
            throw new \Tk\Exception('Cannot add a TaskLog with Task status of: ' . $this->status);
        }
        $log->taskId = $this->taskId;
        $log->save();
        $this->save();
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

    /**
     * @return array<int,Task>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);
        $filter->appendFrom('v_task a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.subject) LIKE LOWER(:lSearch)';
            $w .= 'OR LOWER(a.comments) LIKE LOWER(:lSearch)';
            $w .= 'OR a.task_id = :search';
            $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['taskId'] = $filter['id'];
        }
        if (!empty($filter['taskId'])) {
            if (!is_array($filter['taskId'])) $filter['taskId'] = [$filter['taskId']];
            $filter->appendWhere('AND a.task_id IN :taskId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.task_id NOT IN :exclude', $filter['exclude']);
        }

        if (!empty($filter['companyId'])) {
            $filter->appendWhere('AND a.company_id = :companyId');
        }

        if (!empty($filter['projectId'])) {
            $filter->appendWhere('AND a.project_id = :projectId');
        }

        if (!empty($filter['taskCategoryId'])) {
            if (!is_array($filter['taskCategoryId'])) $filter['taskCategoryId'] = [$filter['taskCategoryId']];
            $filter->appendWhere('AND a.task_category_id IN :taskCategoryId');
        }

//        if (!empty($filter['creatorUserId'])) {
//            if (!is_array($filter['creatorUserId'])) $filter['creatorUserId'] = [$filter['creatorUserId']];
//            $filter->appendWhere('AND a.creator_user_id IN :creatorUserId');
//        }

        if (!empty($filter['assignedUserId'])) {
            if (!is_array($filter['assignedUserId'])) $filter['assignedUserId'] = [$filter['assignedUserId']];
            $filter->appendWhere('AND a.assigned_user_id IN :assignedUserId');
        }

//        if (!empty($filter['closedUserId'])) {
//            if (!is_array($filter['closedUserId'])) $filter['closedUserId'] = [$filter['closedUserId']];
//            $filter->appendWhere('AND a.closed_user_id IN :closedUserId');
//        }

        if (!empty($filter['status'])) {
            if (!is_array($filter['status'])) $filter['status'] = [$filter['status']];
            $filter->appendWhere('AND a.status IN :status');
        }

        if (!empty($filter['priority'])) {
            if (!is_array($filter['priority'])) $filter['priority'] = [$filter['priority']];
            $filter->appendWhere('AND a.priority IN :priority');
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

        if (!$this->taskCategoryId) {
            $errors['taskCategoryId'] = 'Invalid value: taskCategoryId';
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

        if (!$this->priority) {
            $errors['priority'] = 'Invalid value: priority';
        }

        return $errors;
    }

    public static function secondsToDHM(int $minutes): string
    {
        $secs = $minutes*60;
        $str = '';
        if ((int)round($secs/86400) > 0)
            $str .= sprintf('%d days ', round($secs/86400));

        return sprintf('%s%02d:%02d', $str, intval($secs/3600)%24, intval($secs/60%60));
    }
}