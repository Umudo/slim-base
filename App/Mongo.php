<?php

namespace App;


use Interop\Container\ContainerInterface;
use App\Base\ConnectionManager;

class Mongo extends ConnectionManager
{
    /**
     * @var \App\Connection\Mongo[]
     */
    protected static $instances = [];

    /**
     * @param string $instance
     *
     * @return Connection\Mongo
     * @throws \Exception
     */
    public static function getInstance($instance = "default")
    {
        if (isset(self::$instances[$instance])) {
            return self::$instances[$instance];
        }

        if (empty(self::$ci) || self::$ci instanceof ContainerInterface === false) {
            throw new \InvalidArgumentException("Provided container is invalid.");
        }

        $name = "mongo-" . $instance;
        if (self::$ci->has($name)) {
            $client = self::$ci->get($name);
            if ($client instanceof Connection\Mongo) {
                self::$instances[$instance] = $client;
            }

            return $client;
        }

        throw new \Exception("Either container is not set or the container does not have {$name}");
    }

}