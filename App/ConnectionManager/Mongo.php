<?php

namespace App;

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
        parent::getInstance($instance);

        if (isset(self::$instances[$instance])) {
            return self::$instances[$instance];
        }

        $name = self::getPrefix() . $instance;
        
        if (self::$ci->has($name)) {
            $client = self::$ci->get($name);
            if ($client instanceof Connection\Mongo) {
                self::$instances[$instance] = $client;
            } else {
                throw new \Exception("{$name} in container is not an instance of \\App\\Connection\\Mongo");
            }

            return $client;
        }

        throw new \Exception("Either container is not set or the container does not have {$name}");
    }


    public static function getPrefix()
    {
        return "mongo-";
    }
}