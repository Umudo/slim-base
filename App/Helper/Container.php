<?php

namespace App\Helper;

use App\Queue\JobQueue;
use Psr\Container\ContainerInterface;
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

    public static function setContainer(ContainerInterface $container)
    {
        self::$container = $container;
    }

    /**
     * @return ContainerInterface
     *
     * @throws \Exception
     */
    public static function getContainer()
    {
        if (!empty(self::$container)) {
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
        if (!empty(self::$container)) {
            $name = '';
            if ($type === 'default') {
                $name = 'logger';
            }

            if (!empty($name) && self::$container->has($name)) {
                return self::$container->get($name);
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
        if (!empty(self::$container)) {
            if (self::$container->has('jobQueue')) {
                return self::$container->get('jobQueue');
            }

            throw new \Exception('jobQueue is not set in container');
        }

        throw new \Exception('Container is not set.');
    }
}
