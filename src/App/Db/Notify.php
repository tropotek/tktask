<?php
namespace App\Db;

use App\Db\Traits\UserTrait;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Db\Model;
use Tk\Exception;

/**
 * A notify message gets displayed in the users notifications menu and executes a browser notification
 * if the user has granted it permission to do so.
 *
 * See the `events.sql` to view the `evt_delete_expired_notify` event that clears notify records after their ttl.
 *
 * @todo Create a page to view and manage user notifications
 * @see https://webdamn.com/build-push-notification-system-with-php-mysql/#google_vignette
 */
class Notify extends Model
{
    use UserTrait;

    const int DEFAULT_TTL = 60*12;

    public int        $notifyId   = 0;
    public ?int       $userId     = null;
    public string     $title      = '';
    public string     $message    = '';
    public string     $url        = '';         // note: popup blockers will request permission
    public string     $icon       = '';
    public ?\DateTime $readAt     = null;       // Notice read by viewing notice dropdown (not fully implemented yet)
    public ?\DateTime $notifiedAt = null;       // Notification sent to browser
    public bool       $isRead     = false;
    public bool       $isNotified = false;
    public int        $ttlMins    = 0;
    public ?\DateTime $expiry     = null;
    public ?\DateTime $created    = null;


    public function __construct()
    {
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
    public static function create(
        int $userId,
        string $title,
        string $message,
        string $url = '',
        string $icon = '',
        int $ttlMins = self::DEFAULT_TTL
    ): self
    {
        if (empty(trim($title)) || empty(trim($message)) || $ttlMins <= 0) {
            throw new Exception("empty title, message or ttl value");
        }

        $obj = new self();
        $obj->userId  = $userId;
        $obj->title   = $title;
        $obj->message = $message;
        $obj->url     = $url;
        $obj->icon    = $icon;
        $obj->ttlMins = $ttlMins;
        $obj->save();

        return $obj;
    }

    public static function notifyByPermission(
        int $permission,
        string $title,
        string $message,
        string $url = '',
        string $icon = '',
        int $ttlMins = self::DEFAULT_TTL
    ): bool
    {
        $users = User::findFiltered(['permission' => $permission, 'active' => true]);
        foreach ($users as $user) {
            self::create($user->userId, $title, $message, $url, $icon, $ttlMins)->save();
        }
        return true;
    }

    public static function setNotified(array $notifyIds): bool
    {
        if (!$notifyIds) return true;

        return (false !== Db::execute("
            UPDATE notify SET notified_at = NOW()
            WHERE notify_id IN :notifyIds",
            compact('notifyIds')
        ));
    }

    public static function markRead(array $notifyIds): bool
    {
        if (!$notifyIds) return true;

        return (false !== Db::execute("
            UPDATE notify SET read_at = NOW()
            WHERE notify_id IN :notifyIds",
            compact('notifyIds')
        ));
    }

    public static function markAllRead(int $userId): bool
    {
        return false !== Db::execute("
            UPDATE notify SET read_at = NOW()
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
        $filter->appendFrom(static::getPrimaryTable() . ' a');

        if (!empty($filter['search'])) {
            $filter['lSearch'] = '%' . $filter['search'] . '%';
            $w  = 'LOWER(a.title) LIKE LOWER(:lSearch)';
            $w .= 'OR a.notify_id = :search OR ';
            $filter->appendWhere('AND (%s)', $w);
        }

        if (!empty($filter['id'])) {
            $filter['notifyId'] = $filter['id'];
        }
        if (!empty($filter['notifyId'])) {
            if (!is_array($filter['notifyId'])) $filter['notifyId'] = [$filter['notifyId']];
            $filter->appendWhere('AND a.notify_id IN :notifyId');
        }

        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = [$filter['exclude']];
            $filter->appendWhere('AND a.notify_id NOT IN :exclude', $filter['exclude']);
        }

        if (!empty($filter['userId'])) {
            $filter->appendWhere('AND a.user_id = :userId');
        }

        if (!empty($filter['url'])) {
            $filter->appendWhere('AND a.url = :url');
        }

        if (!empty($filter['reference'])) {
            $filter->appendWhere('AND a.reference = :reference');
        }

        if (is_bool(truefalse($filter['isRead'] ?? null))) {
            $filter['isRead'] = truefalse($filter['isRead']);
            $filter->appendWhere('AND a.is_read = :isRead');
        }

        if (is_bool(truefalse($filter['isNotified'] ?? null))) {
            $filter['isNotified'] = truefalse($filter['isNotified']);
            $filter->appendWhere('AND a.is_notified = :isNotified');
        }

        return Db::query("
            SELECT *
            FROM {$filter->getSql()}",
            $filter->all(),
            self::class
        );
    }

}
