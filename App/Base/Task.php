<?php
namespace App\Base;

use App\ConnectionManager\RedisManager;
use App\Helper\Container;
use App\Interfaces\Task as TaskInterface;

abstract class Task implements TaskInterface
{
    private $rk;
    private $rk_timeout;

    /**
     * @return bool
     */
    abstract protected function isActive() : bool;

    /**
     * @return bool
     */
    abstract protected function isLogEnabled() : bool;

    abstract protected function run();

    public function __construct()
    {
        $this->rk = "task:update_check_time:class:" . get_class($this) . "pid:" . getmypid();

        $schedule = static::schedule();
        $this->rk_timeout = strtotime($schedule['killWhenNotActiveFor'], 0);
    }

    protected function logger($action)
    {
        if (!$this->isLogEnabled()) {
            return;
        }
        Container::getLogger()->addInfo("class:" . get_class($this) . " pid:" . getmypid() . "\naction:" . $action);
    }

    protected function updateCheckTime()
    {
        try {
            RedisManager::getInstance(Container::getContainer()->get("settings")['taskRunner']['redisInstanceName'])->setex($this->rk, $this->rk_timeout, time());
        } catch (\Throwable $e) {
            Container::getLogger()->addCritical($e);
        }
    }

    function start()
    {
        $this->updateCheckTime();
        $this->run();
    }
}