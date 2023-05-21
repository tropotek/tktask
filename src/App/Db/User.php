<?php
namespace App\Db;

use Bs\Db\UserInterface;
use Tk\Date;
use Tk\Db\Mapper\Model;

class User extends Model implements UserInterface
{

    /**
     * permission values
	 * permissions are bit masks that can include on or more bits
	 * requests for permission are ANDed with the user's permissions
	 * if the result is non-zero the user has permission.
     *
     * high-level permissions for specific roles
     */
	const PERM_ADMIN            = 0x00000001; // All permissions
	const PERM_SYSADMIN         = 0x00000002; // Change system settings
	const PERM_MANAGE_STAFF     = 0x00000004; // Manage staff users
    const PERM_MANAGE_USER      = 0x00000008; // Manage base users
	//                            0x00000010; // available

	/**
     * permission groups and descriptions
     */
	const PERMISSION_LIST = [
        self::PERM_ADMIN            => "Admin Full Access",
        self::PERM_SYSADMIN         => "Change Site Settings",
        self::PERM_MANAGE_STAFF     => "Manage Staff",
        self::PERM_MANAGE_USER      => "Manage Users",
    ];

    /**
     * Default Guest user This type should never be saved to storage/DB or be an option to select.
     * It is intended to be the default system user that has not logged in
     * (Access to public pages only)
     */
    const TYPE_GUEST = 'guest';

    /**
     * Site staff user
     */
    const TYPE_STAFF = 'staff';

    /**
     * Base logged-in user type (Access to user pages)
     */
    const TYPE_USER = 'user';


    public int $id = 0;

    public string $uid = '';

    public string $type = self::TYPE_GUEST;

    public int $permissions = 0;

    public string $username = 'guest';

    public string $password = '';

    public string $email = 'guest@null.com';

    public string $name = '';

    public string $notes = '';

    public ?string $timezone = null;

    public bool $active = true;

    public string $hash = '';

    public ?\DateTime $lastLogin = null;

    public ?\DateTime $modified = null;

    public ?\DateTime $created = null;


    public function __construct()
    {
        $this->modified = Date::create();
        $this->created = Date::create();
        $this->timezone = $this->getConfig()->get('php.date.timezone');
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function setUid(string $uid): static
    {
        $this->uid = $uid;
        return $this;
    }

    public function isAdmin(): bool
    {
        return ($this->isStaff() && $this->hasPermission(self::PERM_ADMIN));
    }

    public function isStaff(): bool
    {
        return $this->isType(self::TYPE_STAFF);
    }

    public function isUser(): bool
    {
        return $this->isType(self::TYPE_USER);
    }

    public function isType(string|array $type): bool
    {
        if (!is_array($type)) $type = [$type];
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

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function hasPermission(int $permission): bool
    {
		// non-logged in users have no permissions
		if (!$this->isActive()) return false;
		// admin users have all permissions
		if ((self::PERM_ADMIN & $this->getPermissions()) != 0) return true;
		return ($permission & $this->getPermissions()) != 0;
    }

    public function getPermissions(): int
    {
        return $this->permissions;
    }

    public function setPermissions(int $permissions): static
    {
        $this->permissions = $permissions;
        return $this;
    }

    /**
     * return a list of individual permission values
     * Use for select lists or anywhere you need to list
     * the permissions and lookup their names
     */
    public function getPermissionList(): array
    {
        return array_keys(array_filter(self::PERMISSION_LIST, fn($k) => ($k & $this->permissions), ARRAY_FILTER_USE_KEY));
    }

    public function canMasqueradeAs(UserInterface $msqUser): bool
    {
        if ($this->isAdmin()) return true;
        if ($this->isStaff() && $msqUser->isType(self::TYPE_USER)) return true;
        return false;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): static
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): static
    {
        $this->hash = $hash;
        return $this;
    }

    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTime $lastLogin): static
    {
        $this->lastLogin = $lastLogin;
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

        if (!$this->getName()) {
            $errors['name'] = 'Invalid field value';
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
