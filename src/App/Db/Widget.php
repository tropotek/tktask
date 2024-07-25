<?php
namespace App\Db;

use Bs\Db\Traits\TimestampTrait;
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
        // todo: need to simplify this
        $this->_primaryKey = self::getDataMap()->getPrimaryKey()->getProperty();
        // example of mapper with custom table/view
        //self::getDataMap('test', 'v_test');
        $this->_TimestampTrait();
    }

    // example of mapper with custom map
//    public static function getDataMap(string $table = '', string $view = ''): DataMap
//    {
//        $map = self::$_MAPS[static::class] ?? null;
//        if (!is_null($map)) return $map;
//
//        $map = new DataMap();
//        $map->addType(new Db\Integer('widgetId', 'widget_id'))->setFlag(DataMap::PRI);
//        $map->addType(new Db\Text('name'));
//        $map->addType(new Db\Boolean('active'));
//        $map->addType(new Db\Boolean('enabled'));
//        $map->addType(new Db\Text('notes'));
//        $map->addType(new Db\Json('json_str'));
//        $map->addType(new Db\Date('modified'));
//        $map->addType(new Db\Date('created'));
//
//        // view fields are read only
//        $map->addType(new Db\Boolean('isWorking', 'is_working'), DataMap::READ);
//        $map->addType(new Db\Date('today'), DataMap::READ);
//        $map->addType(new Db\Text('hash'), DataMap::READ);
//
//        self::$_MAPS[static::class] = $map;
//        return $map;
//    }


    public function save(): void
    {
        $map = static::getDataMap();
        $values = $map->getArray($this);

        if ($this->getId()) {
            $values['widget_id'] = $this->widgetId;
            self::getDb()->update('widget', 'widget_id', $values);
        } else {
            unset($values['widget_id']);
            self::getDb()->insert('widget', $values);
            $this->widgetId = self::getDb()->lastInsertId();
        }

        $this->reload();
    }


    public static function get(int $id): ?static
    {
        return self::getDb()->queryOne("
                SELECT *
                FROM v_widget
                WHERE widget_id = :id",
            compact('id'),
            self::class
        );
    }

    public static function getAll(): array
    {
        return self::getDb()->query(
            "SELECT * FROM v_widget",
            null,
            self::class
        );
    }

}
