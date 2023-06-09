<?php
namespace App\Db\Traits;

use App\Db\User;
use App\Db\UserMap;

trait UserTrait
{

    private ?User $_user = null;


    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUser(): ?User
    {
        if (!$this->_user)
            $this->_user = UserMap::create()->find($this->getUserId());
        return $this->_user;
    }

    public function setUser(User $user): static
    {
        $this->_user = $user;
        $this->setUserId($user->getId());
        return $this;
    }

}
