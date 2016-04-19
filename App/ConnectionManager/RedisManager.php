<?php

namespace App\ConnectionManager;


use App\Base\ConnectionManager;

class RedisManager extends ConnectionManager
{
    /**
     * @var \Redis[]
     */
    protected static $instances = [];

    /**
     * @param string $instance
     *
     * @return \Redis
     * @throws \Exception
     */
    public static function getInstance($instance = "default")
    {
        parent::getInstance($instance);

        if (isset(self::$instances[$instance])) {
            return self::$instances[$instance];
        }

        $name = self::getPrefix() . $instance;

        if (self::$ci->has($name)) {
            $client = self::$ci->get($name);
            if ($client instanceof \Redis) {
                self::$instances[$instance] = $client;
            } else {
                throw new \Exception("{$name} in container is not an instance of \\Redis");
            }

            return $client;
        }

        throw new \Exception("Either container is not set or the container does not have {$name}");
    }

    public static function instanceExists($instance)
    {
        return isset(self::$instances[$instance]);
    }

    protected static function getPrefix() {
        return "redis-";
    }

}