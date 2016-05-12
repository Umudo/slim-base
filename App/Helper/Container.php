<?php

namespace App\Helper;

use App\Queue\JobQueue;
use Interop\Container\ContainerInterface;
use Monolog\Logger;

/**
 * Class Container
 * Helper Class for getting the global container. Not to be confused with Slim's Container class.
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
     *
     * @throws \Exception
     */
    public static function getContainer()
    {
        if (self::$isSet) {
            return self::$container;
        }

        throw new \Exception('Container is not set.');
    }

    /**
     * @param string $type
     *
     * @return Logger
     *
     * @throws \Exception
     */
    public static function getLogger($type = 'default')
    {
        if (self::$isSet) {
            $name = '';
            if ($type === 'default') {
                $name = 'logger';
            }

            if (!empty($name) && self::getContainer()->has($name)) {
                return self::getContainer()->get($name);
            }

            throw new \Exception("$type logger is not set in container");
        }

        throw new \Exception('Container is not set.');
    }

    /**
     * @return JobQueue
     *
     * @throws \Exception
     */
    public static function getJobQueue()
    {
        if (self::$isSet) {
            if (self::getContainer()->has('jobQueue')) {
                return self::getContainer()->get('jobQueue');
            }

            throw new \Exception('jobQueue is not set in container');
        }

        throw new \Exception('Container is not set.');
    }
}
