<?php

namespace App\Base;


use Interop\Container\ContainerInterface;

abstract class ConnectionManager
{
    /**
     * @var ContainerInterface
     */
    protected static $ci;

    /**
     * @var Connection[]
     */
    protected static $instances = [];

    public static function getInstance($instance = "default")
    {

    }

    public static function setContainer($ci)
    {
        self::$ci = $ci;
    }
}