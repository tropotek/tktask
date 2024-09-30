<?php
namespace App\Db;

use App\Db\Traits\UserTrait;
use Bs\Db\Traits\CreatedTrait;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Db\Model;
use Tk\Exception;


/**
 *
 * @todo Create a page to view and manage user notifications
 * @see https://webdamn.com/build-push-notification-system-with-php-mysql/#google_vignette
 */
class Notify extends Model
{
    use UserTrait;
    use CreatedTrait;

    const DEFAULT_TTL = 60*12*7;


    public int        $notifyId      = 0;
    public int        $userId        = 0;
    public string     $title         = '';
    public string     $message       = '';
    public string     $url           = '';  // note: popup blockers will request permission
    public string     $icon          = '';
    public ?\DateTime $readOn        = null;
    public bool       $isRead        = false;
    public bool       $isNotified    = false;
    public int        $ttlMins       = 0;
    public ?\DateTime $expiry        = null;
    public \DateTime  $created;


    public function __construct()
    {
        $this->_CreatedTrait();
    }

    public function save(): void
    {
        $map = static::getDataMap();

        $values = $map->getArray($this);
        if ($this->notifyId) {
            $values['notify_id'] = $this->notifyId;
            Db::update('notify', 'notify_id', $values);
        } else {
            unset($values['notify_id']);
            Db::insert('notify', $values);
            $this->notifyId = Db::getLastInsertId();
        }

        $this->reload();
    }

    /**
     * create a new notify message
     */
    public static function create(int $userId, string $title, string $message, string $url = '', string $icon = '', int $ttlMins = self::DEFAULT_TTL): self
    {
        if (empty(trim($title)) || empty(trim($message)) || $ttlMins <= 0) {
            throw new Exception("empty title, message or ttl value");
        }

        $obj = new static();
        $obj->userId = $userId;
        $obj->title = $title;
        $obj->message = $message;
        $obj->url = $url;
        $obj->icon = $icon;
        $obj->ttlMins = $ttlMins;
        $obj->save();

        return $obj;
    }

    public static function setNotified(array $notifyIds): bool
    {
        if (!$notifyIds) return true;

        return false !== Db::execute("
            UPDATE notify SET notified_on = NOW()
            WHERE notify_id IN :notifyIds",
            compact('notifyIds')
        );
    }

    public static function find(int $notifyId): ?static
    {
        return Db::queryOne("
            SELECT *
            FROM v_notify
            WHERE notify_id = :notifyId",
            compact('notifyId'),
            self::class
        );
    }

    /**
     * @return array<int,Notify>
     */
    public static function findAll(): array
    {
        return Db::query("
            SELECT *
            FROM v_notify",
            [],
            self::class
        );
    }

    public static function markAllRead(int $userId): bool
    {
        return false !== Db::execute("
            UPDATE notify SET read_on = NOW()
            WHERE user_id = :userId",
            compact('userId')
        );
    }

    /**
     * @return array<int,Notify>
     */
    public static function findFiltered(array|Filter $filter): array
    {
        $filter = Filter::create($filter);

        if (!empty($filter['search'])) {
            $filter['search'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.title) LIKE LOWER(:search) OR ';
            $w .= 'LOWER(a.notify_id) LIKE LOWER(:search) OR ';
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $filter['notifyId'] = $filter['id'];
        }
        if (!empty($filter['notifyId'])) {
            if (!is_array($filter['notifyId'])) $filter['notifyId'] = [$filter['notifyId']];
            $filter->appendWhere('a.notify_id IN :notifyId AND ');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('a.notify_id NOT IN :exclude AND ', $filter['exclude']);
        }

        if (!empty($filter['userId'])) {
            $filter->appendWhere('a.user_id = :userId AND ');
        }

        if (is_bool($filter['isRead'] ?? '')) {
            if ($filter['isRead']) {
                $filter->appendWhere('a.is_read AND ');
            } else {
                $filter->appendWhere('NOT a.is_read AND ');
            }
        }

        if (is_bool($filter['isNotified'] ?? '')) {
            if ($filter['isNotified']) {
                $filter->appendWhere('a.is_notified AND ');
            } else {
                $filter->appendWhere('NOT a.is_notified AND ');
            }
        }

        return Db::query("
            SELECT *
            FROM v_notify a
            {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

}
