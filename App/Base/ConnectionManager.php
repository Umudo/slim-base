<?php

namespace App\Base;


use App\Helper\Container;
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
        if (empty(self::$ci) || self::$ci instanceof ContainerInterface === false) {
            throw new \InvalidArgumentException("Provided container is either empty or not an instance of ContainerInterface.");
        }
    }

    public static function setContainer(ContainerInterface $ci = null)
    {
        if (empty($ci)) {
            $ci = Container::getContainer();
        }

        self::$ci = $ci;
    }

    public static function instanceExists($instance)
    {
        return false;
    }

    protected static function getPrefix() {
        return "";
    }
}