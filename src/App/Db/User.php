<?php
namespace App\Db;

use App\Factory;
use Bs\Db\FileInterface;
use Bs\Db\FileMap;
use Bs\Db\Traits\HashTrait;
use Bs\Db\Traits\TimestampTrait;
use Bs\Db\UserInterface;
use Tk\Date;
use Tk\Db\Mapper\Model;
use Tk\Db\Mapper\Result;

class User extends Model implements UserInterface, FileInterface
{
    use TimestampTrait;
    use HashTrait;

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
        $this->_TimestampTrait();
        $this->timezone = $this->getConfig()->get('php.date.timezone');
    }

    public function getFileList(array $filter = [], ?\Tk\Db\Tool $tool = null): Result
    {
        $filter += ['model' => $this];
        return FileMap::create()->findFiltered($filter, $tool);
    }

    public function getDataPath(): string
    {
        return sprintf('/user/%s/data', $this->getVolatileId());
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

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
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

    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTime $lastLogin): static
    {
        $this->lastLogin = $lastLogin;
        return $this;
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

        if (!$this->getName()) {
            $errors['name'] = 'Invalid field value';
        }
        return $errors;
    }

    public static function checkPassword(string $pwd, array &$errors = []): array
    {
        if (strlen($pwd) < 8) {
            $errors[] = "Password too short!";
        }

        if (!preg_match("#[0-9]+#", $pwd)) {
            $errors[] = "Must include at least one number!";
        }

        if (!preg_match("#[a-zA-Z]+#", $pwd)) {
            $errors[] = "Must include at least one letter!";
        }

        if( !preg_match("#[A-Z]+#", $pwd) ) {
            $errors[] = "Must include at least one Capital!";
        }

        if( !preg_match("#\W+#", $pwd) ) {
            $errors[] = "Must include at least one symbol!";
        }

        return $errors;
    }

    public function rememberMe(int $day = 30): void
    {
        [$selector, $validator, $token] = UserMap::create()->generateToken();

        // remove all existing token associated with the user id
        UserMap::create()->deleteToken($this->getId());

        // set expiration date
        $expired_seconds = time() + 60 * 60 * 24 * $day;

        // insert a token to the database
        $hash_validator = password_hash($validator, PASSWORD_DEFAULT);
        $expiry = date('Y-m-d H:i:s', $expired_seconds);

        if (UserMap::create()->insertToken($this->getId(), $selector, $hash_validator, $expiry)) {
            // TODO: we need to manage the response object so we can call on it when needed.
            //$cookie = Cookie::create('remember', $token, Date::create()->add(new \DateInterval('PT'.$expired_seconds.'S')));
            // use standard php cookie for now.
            setcookie(UserMap::REMEMBER_CID, $token, $expired_seconds);
        }
    }

    /**
     * Remove the `remember me` cookie
     */
    public function removeMe(): void
    {
        $this->getMapper()->deleteToken($this->getId());
        setcookie(UserMap::REMEMBER_CID, '', -1);
    }

    /**
     * Attempt to find a user by the cookie
     * If the user checked the `remember me` checkbox at login this should find the user
     * if a user is found it will be automatically logged into the auth controller
     */
    public static function retrieveMe(): ?User
    {
        $user = null;
        $token = Factory::instance()->getRequest()->cookies->get(UserMap::REMEMBER_CID, '');
        if ($token) {
            [$selector, $validator] = UserMap::create()->parseToken($token);
            $tokens = UserMap::create()->findTokenBySelector($selector);
            if (password_verify($validator, $tokens['hashed_validator'])) {
                $user = UserMap::create()->findBySelector($selector);
                if ($user) {
                    Factory::instance()->getAuthController()->getStorage()->write($user->getUsername());
                }
            }
        }
        return $user;
    }
}
