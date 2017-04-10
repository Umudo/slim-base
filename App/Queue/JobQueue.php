<?php

namespace App\Queue;

use App\ConnectionManager\RedisManager;
use App\Helper\Container;
use Psr\Container\ContainerInterface;

class JobQueue
{
    /**
     * @var string
     */
    protected $queueKey = 'jobqueue';

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
        'redisInstanceName'    => 'default',
        'runFor'               => 60,
        'maxJobFetch'          => 10000,
        'minCronCount'         => 1,
        'maxCronCount'         => 2,
        'pathToPhp'            => '/usr/local/bin/php',
        'consumerCronFileName' => 'jobQueueConsumer.php',
    ];

    public function __construct(array $options = [], ContainerInterface $ci = null)
    {
        $this->options = array_merge($this->options, $options);

        if (isset($this->options['enabled']) && $this->options['enabled'] === true) {
            if (isset($options['runFor']) && preg_match('/[1-9][0-9]*/', $options['runFor'])) {
                $this->options['runFor'] = (int)$options['runFor'];
            }

            if (isset($options['minCronCount']) && preg_match('/[1-9][0-9]*/', $options['minCronCount'])) {
                $this->options['minCronCount'] = (int)$options['minCronCount'];
            }

            if (isset($options['maxCronCount']) && preg_match('/[1-9][0-9]*/', $options['maxCronCount'])) {
                $this->options['maxCronCount'] = (int)$options['maxCronCount'];
            }

            if ($this->options['minCronCount'] > $this->options['maxCronCount']) {
                throw new \Exception('minCronCount can not be higher than maxCronCount');
            }

            $this->redis = RedisManager::getInstance($this->options['redisInstanceName']);

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
        return $this->ci->get('logger');
    }

    public function decide()
    {
        if ($this->options['enabled']) {
            try {
                $job_count_below_time = $this->redis->zCount($this->queueKey, PHP_INT_MIN, time());
                $current_running_queue_consumers = exec('ps aux | grep jobQueueConsumer | grep consume | grep -v grep | wc -l');

                $start_new_count = (int)($job_count_below_time / $this->options['maxJobFetch']);
                if ($current_running_queue_consumers < $this->options['minCronCount']) {
                    $start_new_count += ($this->options['minCronCount'] - $current_running_queue_consumers);
                }

                if ($start_new_count + $current_running_queue_consumers > $this->options['maxCronCount']) {
                    $start_new_count = $this->options['maxCronCount'] - $current_running_queue_consumers;
                }

                for ($i = 0; $i < $start_new_count; ++$i) {
                    exec('(nohup ' . $this->options['pathToPhp'] . ' -f ' . realpath(__DIR__) . '/../../cron/' . $this->options['consumerCronFileName'] . ' consume > /dev/null 2>&1 ) & echo ${!};');
                }
            } catch (\Throwable $e) {
                $this->getLogger()->addError($e);
            }
        }
    }

    public function consume()
    {
        if ($this->options['enabled']) {
            $run_for = time() + $this->options['runFor'];

            while (time() <= $run_for) {
                $multi = $this->redis->multi(\Redis::MULTI);
                $multi->zRange($this->queueKey, 0, $this->options['maxJobFetch']);
                $multi->zRemRangeByRank($this->queueKey, 0, $this->options['maxJobFetch']);
                $result = $multi->exec();

                $jobs = $result[0];

                if (!empty($jobs)) {
                    foreach ($jobs as $job) {
                        try {
                            $job = unserialize($job);
                            if ($job['isStatic']) {
                                call_user_func_array([$job['class'], $job['method']], $job['args']);
                            } else {
                                $class = new \ReflectionClass($job['class']);
                                $object = $class->newInstanceArgs($job['construct_args']);
                                $class->getMethod($job['method'])->invokeArgs($object, $job['args']);
                            }
                        } catch (\Throwable $e) {
                            $this->getLogger()->addNotice($e);
                        }
                    }
                } else {
                    sleep(3);
                }
            }
        }
    }

    /**
     * @param string $class          Full name of the class (with namespaces)
     * @param string $method         method name (static or normal)
     * @param array  $args           method arguments as an array
     * @param int    $delay          higher values will be executed later than the lower ones.
     * @param array  $construct_args if the method is not static and you need to provide __constructor arguments.
     */
    public function addJob($class, $method, $args = [], $delay = 0, $construct_args = [])
    {
        $this->jobs[] = [
            'class'          => $class,
            'method'         => $method,
            'args'           => $args,
            'delay'          => $delay,
            'construct_args' => $construct_args,
        ];
    }

    /**
     * @param string $class          Full name of the class (with namespaces)
     * @param string $method         method name (static or normal)
     * @param array  $args           method arguments as an array
     * @param int    $delay          higher values will be executed later than the lower ones.
     * @param array  $construct_args if the method is not static and you need to provide __constructor arguments.
     *
     * @return bool
     */
    public function addJobNow($class, $method, $args = [], $delay = 0, $construct_args = [])
    {
        if (!$this->options['enabled']) {
            return false;
        }

        $job = [
            'class'          => $class,
            'method'         => $method,
            'args'           => $args,
            'delay'          => $delay,
            'construct_args' => $construct_args,
        ];

        try {
            $class_check = new \ReflectionClass($job['class']);
        } catch (\ReflectionException $e) {
            $this->getLogger()->addNotice($e, array('classname' => $job['class']));

            return false;
        }

        if (!$class_check->hasMethod($job['method'])) {
            $this->getLogger()->addNotice("Method {$job['method']} not found in {$job['class']}", array('classname' => $job['class'], 'methodname' => $job['method']));

            return false;
        }

        $method_check = $class_check->getMethod($job['method']);

        if ($method_check->isPrivate()) {
            $this->getLogger()->addNotice('Method is private', array('classname' => $job['class'], 'methodname' => $job['method']));

            return false;
        }

        $job['isStatic'] = $method_check->isStatic();

        if ($job['isStatic']) {
            unset($job['construct_args']);
        }

        $job['pk'] = $this->generateKeyForJob();

        $this->redis->zAdd($this->queueKey, time() + $job['delay'], serialize($job));

        return true;
    }

    protected function generateKeyForJob()
    {
        return 't' . time() . 'r' . random_int(PHP_INT_MIN, PHP_INT_MAX);
    }

    public function pushJobs()
    {
        if (!empty($this->jobs) && $this->options['enabled']) {
            $multi = $this->redis->multi(\Redis::PIPELINE);

            foreach ($this->jobs as $job) {
                try {
                    try {
                        $class_check = new \ReflectionClass($job['class']);
                    } catch (\ReflectionException $e) {
                        $this->getLogger()->addNotice($e, array('classname' => $job['class']));
                        continue;
                    }

                    if (!$class_check->hasMethod($job['method'])) {
                        $this->getLogger()->addNotice("Method {$job['method']} not found in {$job['class']}", array('classname' => $job['class'], 'methodname' => $job['method']));
                        continue;
                    }

                    $method_check = $class_check->getMethod($job['method']);

                    if ($method_check->isPrivate()) {
                        $this->getLogger()->addNotice('Method is private', array('classname' => $job['class'], 'methodname' => $job['method']));
                        continue;
                    }

                    $job['isStatic'] = $method_check->isStatic();

                    if ($job['isStatic']) {
                        unset($job['construct_args']);
                    }

                    $job['pk'] = $this->generateKeyForJob();

                    $multi->zAdd($this->queueKey, time() + $job['delay'], serialize($job));
                } catch (\Throwable $e) {
                    $this->getLogger()->addInfo($e);
                }
            }

            $multi->exec();
        }
    }
}
