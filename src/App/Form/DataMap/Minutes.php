<?php
namespace App\Form\DataMap;

use Tk\DataMap\DataTypeInterface;

/**
 * map an hour/minute form field to an integer of minutes
 */
class Minutes extends DataTypeInterface
{

    public function getPropertyValue(array $array): int
    {
        $value = parent::getPropertyValue($array);
        return intval(
            (($value['hours'] ?? 0) * 60) +
            ($value['minutes'] ?? 0)
        );
    }

    public function getColumnValue(object $object): array
    {
        $value = parent::getColumnValue($object);
        if ($value !== null) {
            $hours = intval($value / 60);
            $minutes = $value % 60;
            return compact('hours', 'minutes');
        }
        return ['hours' => '0', 'minutes' => '0'];
    }

}

