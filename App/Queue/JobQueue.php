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
    protected $queueKey = "jobqueue";

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

    protected $options = [
        'enabled'              => false,
        'instanceName'         => 'default',
        'runFor'               => 60,
        'minCronCount'         => 1,
        'maxCronCount'         => 10,
        'consumerCronFileName' => 'jobQueueConsumer.php'
    ];

    public function __construct(array $options = [], ContainerInterface $ci = null)
    {
        $this->options = array_merge($this->options, $options);

        if (isset($this->options['enabled']) && $this->options['enabled']) {

            if (!RedisManager::instanceExists($this->options["instanceName"])) {
                throw new \Exception("Redis instance not running.");
            }

            if (isset($options["runFor"]) && preg_match('/[1-9][0-9]*/', $options["runFor"])) {
                $this->options['runFor'] = (int)$options['runFor'];
            }

            if (isset($options["minCronCount"]) && preg_match('/[1-9][0-9]*/', $options["minCronCount"])) {
                $this->options['minCronCount'] = (int)$options['minCronCount'];
            }

            if (isset($options["maxCronCount"]) && preg_match('/[1-9][0-9]*/', $options["maxCronCount"])) {
                $this->options['maxCronCount'] = (int)$options['maxCronCount'];
            }

            $this->redis = RedisManager::getInstance($this->options["instanceName"]);

            if (empty($ci)) {
                $ci = Container::getContainer();
            }

            $this->ci = $ci;
        }
    }

    /**
     * @return \Monolog\Logger
     */
    protected function getLogger()
    {
        return $this->ci->get("logger");
    }

    public function decide()
    {
        try {
            $job_count_below_time = $this->redis->zCount($this->queueKey, PHP_INT_MIN, time());
            $current_running_main_queue_consumers = exec('ps aux | grep jobQueueConsumer | grep decide | grep -v grep | wc -l');

            $main_queue_run_consumers = (int)($job_count_below_time / 10);
            if ($current_running_main_queue_consumers < $this->options['minCronCount']) {
                $main_queue_run_consumers += ($this->options['minCronCount'] - $current_running_main_queue_consumers);
            }

            if ($main_queue_run_consumers + $current_running_main_queue_consumers > $this->options['maxCronCount']) {
                $main_queue_run_consumers = $this->options['maxCronCount'] - $current_running_main_queue_consumers;
            }

            for ($i = 0; $i < $main_queue_run_consumers; $i++) {
                exec('(nohup /usr/bin/php -f ' . realpath(__DIR__) . '' . DIRECTORY_SEPARATOR . $this->options["consumerCronFileName"].' consume 1 > /dev/null 2>&1 ) & echo ${!};');
            }
        } catch (\Throwable $e) {
            $this->getLogger()->addInfo($e->getMessage());
        }
    }

    public function consume()
    {
        $run_for = time() + $this->options['runFor'];

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
                        $this->getLogger()->addInfo($e->getMessage());
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

        try {
            $class_check = new \ReflectionClass($job["class"]);
        } catch (\ReflectionException $e) {
            $this->getLogger()->addNotice($e->getMessage(), array("classname" => $job["class"]));

            return false;
        }

        if (!$class_check->hasMethod($job["method"])) {
            $this->getLogger()->addNotice("Method {$job['method']} not found in {$job['class']}", array("classname" => $job["class"], "methodname" => $job["method"]));

            return false;
        }

        $method_check = $class_check->getMethod($job["method"]);

        if ($method_check->isPrivate()) {
            $this->getLogger()->addNotice("Method is private", array("classname" => $job["class"], "methodname" => $job["method"]));

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
            $multi = $this->redis->multi();

            foreach ($this->jobs as $job) {
                try {
                    $class_check = new \ReflectionClass($job["class"]);
                } catch (\ReflectionException $e) {
                    $this->getLogger()->addNotice($e->getMessage(), array("classname" => $job["class"]));
                    continue;
                }

                if (!$class_check->hasMethod($job["method"])) {
                    $this->getLogger()->addNotice("Method {$job['method']} not found in {$job['class']}", array("classname" => $job["class"], "methodname" => $job["method"]));
                    continue;
                }

                $method_check = $class_check->getMethod($job["method"]);

                if ($method_check->isPrivate()) {
                    $this->getLogger()->addNotice("Method is private", array("classname" => $job["class"], "methodname" => $job["method"]));
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