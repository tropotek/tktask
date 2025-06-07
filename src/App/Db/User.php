<?php
namespace App\Db;

use Bs\Auth;
use Bs\Traits\AuthTrait;
use Bs\Db\UserInterface;
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

    /**
     * permission values
     * permissions are bit masks that can include on or more bits
     * requests for permission are ANDed with the user's permissions
     * if the result is non-zero the user has permission.
     */
    const int PERM_ADMIN            = 0x1;      // Admin
    const int PERM_SYSADMIN         = 0x2;      // Change system settings
    const int PERM_MANAGE_STAFF     = 0x4;      // Manage staff
    //const int PERM_   = 0x8;    // available

    const array PERMISSION_LIST = [
        self::PERM_ADMIN            => "Admin",
        self::PERM_SYSADMIN         => "Manage Settings",
        self::PERM_MANAGE_STAFF     => "Manage Staff",
    ];

    const string TYPE_STAFF = 'staff';

    const array TITLE_LIST = [
        'Mr', 'Mrs', 'Ms', 'Dr',
        'Prof', 'Esq', 'Hon', 'Messrs', 'Mmes',
        'Msgr', 'Rev', 'Jr', 'Sr', 'St'
    ];

    public int        $userId        = 0;
    public string     $uid           = '';
    public string     $type          = self::TYPE_STAFF;

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

    public function getImageUrl(): ?Uri
    {
        $color = Color::createRandom($this->userId);
        $initials = strtoupper($this->givenName[0] ?? '').strtolower($this->familyName[0] ?? '');
        $initials = $initials ?: strtoupper($this->username[0] ?? 'A');
        $img = Image::createAvatar($initials, $color);
        $b64 = base64_encode($img->getContents());
        return Uri::create('data:image/png;base64,' . $b64);
    }

    public static function getHomeUrl(string $type = ''): Uri
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
        $filter->appendFrom('v_user a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.given_name) LIKE LOWER(:lSearch)';
            $w .= 'OR LOWER(a.family_name) LIKE LOWER(:lSearch)';
            $w .= 'OR LOWER(a.email) LIKE LOWER(:lSearch)';
            $w .= 'OR LOWER(a.uid) LIKE LOWER(:lSearch)';
            $w .= 'OR a.user_id LIKE :search';
            $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['userId'] = $filter['id'];
        }
        if (!empty($filter['userId'])) {
            if (!is_array($filter['userId'])) $filter['userId'] = [$filter['userId']];
            $filter->appendWhere('AND a.user_id IN :userId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.user_id NOT IN :exclude', $filter['exclude']);
        }

        if (!empty($filter['uid'])) {
            $filter->appendWhere('AND a.uid = :uid');
        }

        if (!empty($filter['hash'])) {
            $filter->appendWhere('AND a.hash = :hash');
        }

        if (!empty($filter['username'])) {
            $filter->appendWhere('AND a.username = :username');
        }

        if (!empty($filter['email'])) {
            $filter->appendWhere('AND a.email = :email');
        }

        if (!empty($filter['permission'])) {
            $filter->appendWhere('AND (a.permissions & :permission) != 0');
        }

        if (is_bool(truefalse($filter['active'] ?? null))) {
            $filter['active'] = truefalse($filter['active']);
            $filter->appendWhere('AND a.active = :active');
        }

        return Db::query("
            SELECT *
            FROM {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

}
