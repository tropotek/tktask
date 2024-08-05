<?php
namespace App\Db;

use Bs\Db\Traits\TimestampTrait;
use Tt\Db;
use Tt\DbModel;

class Widget extends DbModel
{
    use TimestampTrait;

    public int        $widgetId  = 0;
    public string     $name      = '';
    public bool       $active    = true;
    public bool       $enabled   = false;
    public string     $notes     = '';
    public string     $blobData  = '';
    public ?string    $setType   = null;
    public string     $enumType  = 'core';
    public float      $rate      = 1.0;
    public ?\DateTime $timeStamp = null;
    public ?\DateTime $dateTime  = null;
    public ?\DateTime $date      = null;
    public ?\DateTime $time      = null;
    public ?\DateTime $year      = null;
    public ?\stdClass $jsonStr   = null;
    public ?\DateTime $modified  = null;
    public ?\DateTime $created   = null;

    public bool       $isWorking = false;
    public ?\DateTime $today     = null;
    public string     $hash      = '';


    public function __construct()
    {
        parent::__construct();
        $this->_TimestampTrait();
    }

    public function save(): void
    {
        $map = static::getDataMap();
        $values = $map->getArray($this);
        if ($this->getId()) {
            $values['widget_id'] = $this->widgetId;
            Db::update('widget', 'widget_id', $values);
        } else {
            unset($values['widget_id']);
            Db::insert('widget', $values);
            $this->widgetId = Db::lastInsertId();
        }
        $this->reload();
    }

    public static function get(int $id): ?static
    {
        return Db::queryOne("
                SELECT *
                FROM v_widget
                WHERE widget_id = :id",
            compact('id'),
            self::class
        );
    }

    public static function getSome(array $ids): array
    {
        return Db::query("
                SELECT *
                FROM v_widget
                WHERE widget_id IN :ids",
            compact('ids'),
            self::class
        );
    }

    public static function getAll(): array
    {
        return Db::query(
            "SELECT * FROM v_widget",
            null,
            self::class
        );
    }

}
