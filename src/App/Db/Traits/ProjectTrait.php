<?php
namespace App\Db\Traits;

use App\Db\Project;

trait ProjectTrait
{
    private ?Project $_project = null;

    public function getProject(): ?Project
    {
        if (!$this->_project) {
            $this->_project = Project::find((int)$this->projectId);
        }
        return $this->_project;
    }

}
