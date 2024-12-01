<?php

namespace App\Util;

/**
 * Custom methods for the application
 */
class Tools
{

    /**
     * Convert minutes to a `hh:mm` string
     */
    public static function mins2Str(int $minutes): string
    {
        $h = floor($minutes / 60);
        $m = $minutes - ($h * 60);
        return sprintf('%02d:%02d', $h, $m);
    }

    /**
     * Convert a string in the format of `hh:mm` to minutes
     */
    public static function str2min(string $str): int
    {
        [$h, $m] = explode(':', $str);
        return (int)(((int)$h*60)+(int)$m);
    }


}