<?php
namespace App\Db;

use Bs\Auth;
use Bs\Traits\AuthTrait;
use Bs\Db\UserInterface;
use Bs\Traits\TimestampTrait;
use Tk\Color;
use Tk\Config;
use Tk\Image;
use Tk\Uri;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Db\Model;

class User extends Model implements UserInterface
{
    use AuthTrait;
    use TimestampTrait;

    /**
     * permission values
     * permissions are bit masks that can include on or more bits
     * requests for permission are ANDed with the user's permissions
     * if the result is non-zero the user has permission.
     *
     * @todo move all permission functions to app level
     */
    const int PERM_ADMIN            = 0x1; // Admin
    const int PERM_SYSADMIN         = 0x2; // Change system settings
    const int PERM_MANAGE_STAFF     = 0x4; // Manage staff
    const int PERM_MANAGE_MEMBERS   = 0x8; // Manage members
    //                            0x10; // available

    const array PERMISSION_LIST = [
        self::PERM_ADMIN            => "Admin",
        self::PERM_SYSADMIN         => "Manage Settings",
        self::PERM_MANAGE_STAFF     => "Manage Staff",
        self::PERM_MANAGE_MEMBERS   => "Manage Members",
    ];

    const string TYPE_STAFF = 'staff';
    const string TYPE_MEMBER = 'member';

    const array TITLE_LIST = [
        'Mr', 'Mrs', 'Ms', 'Dr',
        'Prof', 'Esq', 'Hon', 'Messrs', 'Mmes',
        'Msgr', 'Rev', 'Jr', 'Sr', 'St'
    ];

    public int        $userId        = 0;
    public string     $uid           = '';
    public string     $type          = self::TYPE_MEMBER;

    public string     $title         = '';
    public string     $givenName     = '';
    public string     $familyName    = '';
    public string     $nameShort     = '';
    public string     $nameLong      = '';
    public string     $phone         = '';
    public string     $address       = '';
    public string     $city          = '';
    public string     $state         = '';
    public string     $postcode      = '';
    public string     $country       = '';
    public string     $template      = '';
    public string     $dataPath      = '';

    public int        $permissions   = 0;
    public string     $username      = '';
    public string     $password      = '';
    public string     $email         = '';
    public string     $timezone      = '';
    public bool       $active        = true;
    public string     $sessionId     = '';
    public ?string    $hash          = null;
    public ?\DateTime $lastLogin     = null;

    public \DateTimeImmutable $modified;
    public \DateTimeImmutable $created;


    public function __construct()
    {
        $this->timezone = Config::instance()->get('php.date.timezone');
        $this->modified = new \DateTimeImmutable();
        $this->created  = new \DateTimeImmutable();
    }

    public function save(): void
    {
        $map = static::getDataMap();

        // Remove permissions for non-staff users
        if ($this->userId && $this->isType(self::TYPE_MEMBER)) {
            $this->getAuth()->permissions = Auth::PERM_NONE;
            $this->getAuth()->save();
        }

        $values = $map->getArray($this);
        if ($this->userId) {
            $values['user_id'] = $this->userId;
            Db::update('user', 'user_id', $values);
        } else {
            unset($values['user_id']);
            Db::insert('user', $values);
            $this->userId = Db::getLastInsertId();
        }

        $this->reload();
    }

    public function getFileList(array $filter = []): array
    {
        $filter += ['model' => $this];
        return File::findFiltered($filter);
    }

    public function getDataPath(): string
    {
        return $this->dataPath;
    }

    public function getImageUrl(): ?Uri
    {
        $color = Color::createRandom($this->userId);
        $img = Image::createAvatar($this->nameShort ?: $this->username, $color);
        $b64 = base64_encode($img->getContents());
        return Uri::create('data:image/png;base64,' . $b64);
    }

    public function getHomeUrl(): Uri
    {
        return Uri::create('/dashboard');
    }

    public function isAdmin(): bool
    {
        return $this->getAuth()->isAdmin();
    }

    public function isStaff(): bool
    {
        return $this->isType(self::TYPE_STAFF);
    }

    public function isMember(): bool
    {
        return $this->isType(self::TYPE_MEMBER);
    }

    public function isType(string|array $type): bool
    {
        if (!is_array($type)) $type = [$type];
        foreach ($type as $r) {
            if (trim($r) == trim($this->type)) {
                return true;
            }
        }
        return false;
    }

    public function hasPermission(int $permission): bool
    {
        return $this->getAuth()->hasPermission($permission);
    }

    /**
     * Validate this object's current state and return an array
     * with error messages. This will be useful for validating
     * objects for use within forms.
     */
    public function validate(): array
    {
        $errors = [];

        if (!$this->givenName) {
            $errors['givenName'] = 'Invalid field value';
        }

        $list = ['sn-admin', 'tn-admin'];
        if ($this->template && !in_array($this->template, $list)) {
            $errors['template'] = 'Invalid template selected';
        }

        return $errors;
    }

    /**
     * Get the currently logged-in user if any
     * Only returns the authed model if instance of self
     */
    public static function getAuthUser(): ?self
    {
        $user = Auth::getAuthUser()?->getDbModel();
        if ($user instanceof self) {
            return $user;
        }
        return null;
    }

    public static function find(int $userId): ?self
    {
        return Db::queryOne("
            SELECT *
            FROM v_user
            WHERE user_id = :userId",
            compact('userId'),
            self::class
        );
    }

    /**
     * @return array<int,User>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM v_user",
            [],
            self::class
        );
    }

    public static function findByUsername(string $username): ?self
    {
        $username = trim($username);
        if(empty($username)) return null;

        return Db::queryOne("
            SELECT *
            FROM v_user
            WHERE username = :username",
            compact('username'),
            self::class
        );
    }

    public static function findByEmail(string $email): ?self
    {
        $email = trim($email);
        if(empty($email)) return null;

        return Db::queryOne("
            SELECT *
            FROM v_user
            WHERE email = :email",
            compact('email'),
            self::class
        );
    }

    public static function findByHash(string $hash): ?self
    {
        $hash = trim($hash);
        if(empty($hash)) return null;

        return Db::queryOne("
            SELECT *
            FROM v_user
            WHERE hash = :hash",
            compact('hash'),
            self::class
        );
    }

    /**
     * @return array<int,User>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.given_name) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.family_name) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.email) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.uid) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.user_id) LIKE LOWER(:search) OR ';
            $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['userId'] = $filter['id'];
        }
        if (!empty($filter['userId'])) {
            if (!is_array($filter['userId'])) $filter['userId'] = [$filter['userId']];
            $filter->appendWhere('a.user_id IN :userId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.user_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['uid'])) {
            $filter->appendWhere('a.uid = :uid AND ');
        }

        if (!empty($filter['hash'])) {
            $filter->appendWhere('a.hash = :hash AND ');
        }

        if (!empty($filter['type'])) {
            if (!is_array($filter['type'])) $filter['type'] = [$filter['type']];
            $filter->appendWhere('a.type IN :type AND ');
        }

        if (!empty($filter['username'])) {
            $filter->appendWhere('a.username = :username AND ');
        }

        if (!empty($filter['email'])) {
            $filter->appendWhere('a.email = :email AND ');
        }

        if (is_bool(truefalse($filter['active'] ?? null))) {
            $filter['active'] = truefalse($filter['active']);
            $filter->appendWhere('a.active = :active AND ');
        }

        return Db::query("
            SELECT *
            FROM v_user a
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

}
