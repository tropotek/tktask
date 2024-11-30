<?php
namespace App\Db\Traits;

use App\Db\Task;

trait TaskTrait
{
    private ?Task $_task = null;

    public function getTask(): ?Task
    {
        if (!$this->_task) $this->_task = Task::find($this->taskId);
        return $this->_task;
    }

}
