<?php

namespace App;

final class Utilities
{
    public static function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1000));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1000, $pow);
        return round($bytes, $precision) . $units[$pow];
    }

    public static function guessSearchFilterFromQuery(string $q)
    {
        if (Utilities::isIP($q))
            return "ip";
        elseif (Utilities::isMAC($q))
            return "mac";
        else
            return "name";
    }

    private static function isIP(string $q)
    {
        return preg_match("/(\d+\.){2}/", $q) === 1;
    }

    private static function isMAC(string $q)
    {
        return preg_match("/([a-fA-F]*\d*(\:|\-)){2}/", $q) === 1;
    }
}
