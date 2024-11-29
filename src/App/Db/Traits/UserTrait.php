<?php
namespace App\Db\Traits;

use App\Db\User;

trait UserTrait
{
    private ?User $_user = null;

    public function getUser(): ?User
    {
        if (!$this->_user) {
            $this->_user = User::find($this->userId);
        }
        return $this->_user;
    }

}
