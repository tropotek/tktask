<?php
namespace App\Db;

use Bs\Db\Traits\TimestampTrait;
use Tt\DataMap\DataMap;
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
    public ?\stdClass $jsonStr   = null;
    public ?\DateTime $modified  = null;
    public ?\DateTime $created   = null;

    public bool       $isWorking = false;
    public ?\DateTime $today     = null;
    public string     $hash      = '';

    public function __construct()
    {

        $this->_primaryKey = 'widgetId';
        $this->_TimestampTrait();
    }


//    public static function createDataMap(array $dbMeta = []): DataMap
//    {
//        $map = new DataMap();
//        $map->addDataType(new Db\Integer('widgetId', 'widget_id'))->setAttribute(DataMap::ATTR_PRIMARY_KEY);
//        $map->addDataType(new Db\Text('name'));
//        $map->addDataType(new Db\Boolean('active'));
//        $map->addDataType(new Db\Boolean('enabled'));
//        $map->addDataType(new Db\Text('notes'));
//        $map->addDataType(new Db\Text('blobData', 'blob_data'));
//        $map->addDataType(new Db\DateTime('timeStamp', 'time_stamp'));
//        $map->addDataType(new Db\DateTime('dateTime', 'date_time'));
//        $map->addDataType(new Db\Date('date'));
//        $map->addDataType(new Db\Time('time'));
//        $map->addDataType(new Db\Json('json_str'));
//        $map->addDataType(new Db\Date('modified'));
//        $map->addDataType(new Db\Date('created'));
//
//        // view fields are read only
//        $map->addDataType(new Db\Boolean('isWorking', 'is_working'), DataMap::READ);
//        $map->addDataType(new Db\Date('today'), DataMap::READ);
//        $map->addDataType(new Db\Text('hash'), DataMap::READ);
//
//        return $map;
//    }


    public function save(): void
    {
        $vals = $this->__unmap([
            'name'      => 'trim',
            'active'    => 'boolval',
            'enabled'   => 'boolval',
            'notes'     => 'trim',
            'blobData'  => '',
            'timeStamp' => '\Tk\Db\Db::mysqlDateTime',
            'dateTime'  => '\Tk\Db\Db::mysqlDateTime',
            'date'      => '\Tk\Db\Db::mysqlDate',
            'time'      => '\Tk\Db\Db::mysqlTime',
            'jsonStr'   => 'json_encode',
        ]);

        if ($this->getId()) {
            $vals['widget_id'] = $this->widgetId;
            self::getDb()->update('widget', 'widget_id', $vals);
        } else {
            unset($vals['widget_id']);
            self::getDb()->insert('widget', $vals);
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
