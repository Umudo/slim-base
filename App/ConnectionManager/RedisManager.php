<?php

namespace App\ConnectionManager;


use App\Base\ConnectionManager;
use App\Connection\Redis as RedisConnection;

class RedisManager extends ConnectionManager
{
    /**
     * @var RedisConnection[]
     */
    protected static $instances = [];

    public static function getInstance($instance = "default")
    {
        parent::getInstance($instance);

        if (isset(self::$instances[$instance])) {
            return self::$instances[$instance];
        }

        $name = self::getPrefix() . $instance;

        if (self::$ci->has($name)) {
            $client = self::$ci->get($name);
            if ($client instanceof RedisConnection) {
                self::$instances[$instance] = $client;
            } else {
                throw new \Exception("{$name} in container is not an instance of \\App\\Connection\\Redis");
            }

            return $client;
        }

        throw new \Exception("Either container is not set or the container does not have {$name}");
    }

    public static function getPrefix() {
        return "redis-";
    }

}