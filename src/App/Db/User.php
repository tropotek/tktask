<?php
namespace App\Db;

use Tk\Date;
use Tk\Db\Mapper\Model;

/**
 *
 * @author Tropotek <http://www.tropotek.com/>
 */
class User extends Model
{

    /**
     * Default Guest user This type should never be saved to storage
     * It is intended to be the default system user that has not logged in
     * (Access to public pages only)
     */
    const TYPE_GUEST = 'guest';
    /**
     * Administration user (Access to the admin area)
     */
    const TYPE_ADMIN = 'admin';
    /**
     * Base logged-in user type (Access to user pages)
     */
    const TYPE_MEMBER = 'member';


    public int $id = 0;

    public string $uid = '';

    public string $type = '';

    public string $username = '';

    public string $password = '';

    public string $nameFirst = '';

    public string $nameLast = '';

    public string $email = '';

    public string $notes = '';

    public ?\DateTime $lastLogin = null;

    public bool $active = false;

    public bool $del = false;

    public ?\DateTime $modified = null;

    public ?\DateTime $created = null;


    public function __construct()
    {
        $this->modified = Date::create();
        $this->created = Date::create();
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function setUid(string $uid): User
    {
        $this->uid = $uid;
        return $this;
    }

    public function hasType(string|array $type): bool
    {
        if (func_num_args() > 1) $type = func_get_args();
        else if (!is_array($type)) $type = array($type);
        foreach ($type as $r) {
            if (trim($r) == trim($this->getType())) {
                return true;
            }
        }
        return false;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): User
    {
        $this->type = $type;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(?string $username): User
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): User
    {
        $this->password = $password;
        return $this;
    }

    public function getName(): string
    {
        $name = trim($this->getNameFirst() . ' ' . $this->getNameLast());
        if (!trim($name)) $name = $this->getUsername();
        return $name;
    }

    public function setName(?string $name): User
    {
        $name = trim($name);
        if ( preg_match('/\s/',$name) ) {
            $this->setNameFirst(substr($name, 0, strpos($name, ' ')));
            $this->setNameLast(substr($name, strpos($name, ' ') + 1));
        } else {
            $this->setNameFirst($name);
        }
        return $this;
    }

    public function getNameFirst(): string
    {
        return $this->nameFirst;
    }

    public function setNameFirst(string $nameFirst): User
    {
        $this->nameFirst = $nameFirst;
        return $this;
    }

    public function getNameLast(): string
    {
        return $this->nameLast;
    }

    public function setNameLast(string $nameLast): User
    {
        $this->nameLast = $nameLast;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(?string $email): User
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getNotes(): string
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     * @return User
     */
    public function setNotes(string $notes): User
    {
        $this->notes = $notes;
        return $this;
    }

    public function isDel(): bool
    {
        return $this->del;
    }

    public function setDel(bool $del): User
    {
        $this->del = $del;
        return $this;
    }

    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTime $lastLogin): User
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): User
    {
        $this->active = $active;
        return $this;
    }

    public function getModified(): ?\DateTime
    {
        return $this->modified;
    }

    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    /**
     * Validate this object's current state and return an array
     * with error messages. This will be useful for validating
     * objects for use within forms.
     */
    public function validate(): array
    {
        $errors = [];
        $mapper = $this->getMapper();

        if (!$this->getNameFirst()) {
            $errors['nameFirst'] = 'Invalid field value';
        }
        if (!$this->getNameLast()) {
            $errors['nameLast'] = 'Invalid field value';
        }

        if (!$this->getUsername()) {
            $errors['username'] = 'Invalid field username value';
        } else {
            $dup = $mapper->findByUsername($this->getUsername());
            if ($dup && $dup->getId() != $this->getId()) {
                $errors['username'] = 'This username is already in use';
            }
        }
        if (!filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }
        return $errors;
    }
}
