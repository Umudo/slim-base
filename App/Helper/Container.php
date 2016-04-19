<?php

namespace App\Helper;


use App\Queue\JobQueue;
use Interop\Container\ContainerInterface;

/**
 * Class Container
 * Helper Class for getting the global container. Not to be confused with Slim's Container class.
 *
 * @package App\Helper
 */
class Container
{
    /**
     * @var ContainerInterface
     */
    private static $container;

    private static $isSet = false;

    public static function setContainer(ContainerInterface $container)
    {
        self::$container = $container;
        self::$isSet = true;
    }

    /**
     * @return ContainerInterface
     * @throws \Exception
     */
    public static function getContainer()
    {
        if (self::$isSet) {
            return self::$container;
        }

        throw new \Exception("Container is not set.");
    }

    /**
     * @return JobQueue
     * @throws \Exception
     */
    public static function getJobQueue()
    {
        if (self::$isSet) {
            if (self::getContainer()->has("jobQueue")) {
                return self::getContainer()->get("jobQueue");
            }

            throw new \Exception("jobQueue is not set in container");
        }

        throw new \Exception("Container is not set.");
    }
}