<?php

namespace App\Helper;

class Uri
{
    protected static $basePath;

    public static function uri($url)
    {
        if (stripos($url, '://') === false) {
            return self::getBasePath() . ltrim($url, '/');
        }

        return $url;
    }

    public static function setBasePath($url)
    {
        self::$basePath = rtrim($url, '/') . '/';
    }

    public static function getBasePath()
    {
        return self::$basePath;
    }
}
