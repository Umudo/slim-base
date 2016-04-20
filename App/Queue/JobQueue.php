<?php

namespace App\Queue;

use App\ConnectionManager\RedisManager;
use App\Helper\Container;
use Interop\Container\ContainerInterface;

/**
 * Class JobQueue
 * @package App\Queue
 *
 * This class uses redis as a backend to push jobs.
 * Jobs are class methods that are either static or object methods.
 */
class JobQueue
{
    /**
     * @var string
     */
    protected $instanceName = "default";
    protected $queueKey     = "jobqueue";

    /**
     * @var int
     */
    protected $runFor = 60;

    /**
     * @var array
     */
    protected $jobs = [];

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var ContainerInterface
     */
    protected $ci;

    public function __construct(array $options = [], ContainerInterface $ci = null)
    {
        if (isset($options["instanceName"]) && RedisManager::instanceExists($options["instanceName"])) {
            $this->instanceName = $options["instanceName"];
        }

        if (isset($options["runFor"]) && preg_match('/[1-9][0-9]*/', $options["runFor"])) {
            $this->runFor = (int)$options["runFor"];
        }

        $this->redis = RedisManager::getInstance($this->instanceName);

        if (empty($ci)) {
            $ci = Container::getContainer();
        }

        $this->ci = $ci;
    }

    public function consume()
    {
        $run_for = time() + $this->runFor;

        /* @var \Monolog\Logger $logger */
        $logger = $this->ci->get("logger");

        while (time() <= $run_for) {
            $jobs = $this->redis->zRangeByScore($this->queueKey, PHP_INT_MIN, time());
            $this->redis->zRemRangeByScore($this->queueKey, PHP_INT_MIN, time());

            if (!empty($jobs)) {
                foreach ($jobs as $job) {
                    try {
                        $job = unserialize($job);
                        if ($job["isStatic"]) {
                            call_user_func_array([$job["class"], $job["method"]], $job["args"]);
                        } else {
                            $class = new \ReflectionClass($job["class"]);
                            $object = $class->newInstanceArgs($job["constructor_args"]);
                            $class->getMethod($job["method"])->invokeArgs($object, $job["args"]);
                        }
                    } catch (\Throwable $e) {
                        $logger->addInfo($e->getMessage());
                    }
                }
            } else {
                sleep(1);
            }
        }
    }

    /**
     * @param string $class Full name of the class (with namespaces)
     * @param string $method method name (static or normal)
     * @param array  $args method arguments as an array
     * @param int    $time when the job should be executed
     * @param array  $construct_args if the method is not static and you need to provide __constructor arguments.
     *
     * @throws \Exception
     */
    public function addJob($class, $method, $args = [], $time = 0, $construct_args = [])
    {
        $this->jobs[] = [
            "class"          => $class,
            "method"         => $method,
            "args"           => $args,
            "time"           => $time,
            "construct_args" => $construct_args
        ];
    }

    public function addJobNow($class, $method, $args = [], $time = 0, $construct_args = [])
    {
        $job = [
            "class"          => $class,
            "method"         => $method,
            "args"           => $args,
            "time"           => $time,
            "construct_args" => $construct_args
        ];

        /* @var \Monolog\Logger $logger */
        $logger = $this->ci->get("logger");

        try {
            $class_check = new \ReflectionClass($job["class"]);
        } catch (\ReflectionException $e) {
            $logger->addNotice($e->getMessage(), array("classname" => $job["class"]));

            return false;
        }

        if (!$class_check->hasMethod($job["method"])) {
            $logger->addNotice("Method {$job['method']} not found in {$job['class']}", array("classname" => $job["class"], "methodname" => $job["method"]));

            return false;
        }

        $method_check = $class_check->getMethod($job["method"]);

        if ($method_check->isPrivate()) {
            $logger->addNotice("Method is private", array("classname" => $job["class"], "methodname" => $job["method"]));

            return false;
        }

        $job["isStatic"] = $method_check->isStatic();

        if ($job["isStatic"]) {
            unset($job["construct_args"]);
        }

        $job["pk"] = $this->generateKeyForJob();

        $this->redis->zAdd($this->queueKey, time() + $job["time"], serialize($job));

        return true;
    }

    protected function generateKeyForJob()
    {
        return "t" . time() . "r" . random_int(PHP_INT_MIN, PHP_INT_MAX);
    }

    public function pushJobs()
    {
        if (!empty($this->jobs)) {
            /* @var \Monolog\Logger $logger */
            $logger = $this->ci->get("logger");

            $multi = $this->redis->multi();

            foreach ($this->jobs as $job) {
                try {
                    $class_check = new \ReflectionClass($job["class"]);
                } catch (\ReflectionException $e) {
                    $logger->addNotice($e->getMessage(), array("classname" => $job["class"]));
                    continue;
                }

                if (!$class_check->hasMethod($job["method"])) {
                    $logger->addNotice("Method {$job['method']} not found in {$job['class']}", array("classname" => $job["class"], "methodname" => $job["method"]));
                    continue;
                }

                $method_check = $class_check->getMethod($job["method"]);

                if ($method_check->isPrivate()) {
                    $logger->addNotice("Method is private", array("classname" => $job["class"], "methodname" => $job["method"]));
                    continue;
                }

                $job["isStatic"] = $method_check->isStatic();

                if ($job["isStatic"]) {
                    unset($job["construct_args"]);
                }

                $job["pk"] = $this->generateKeyForJob();

                $multi->zAdd($this->queueKey, time() + $job["time"], serialize($job));
            }

            $multi->exec();
        }
    }
}