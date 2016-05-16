<?php

namespace App\Interfaces;


interface Task
{
    /**
     * @return array
     * return ['runEvery' => '* * * * *','killWhenNotActiveFor' => '10mins','activeProcessCount' => 2];
     *  runEvery => optional, same as linux crontab expression, if not present the task will be treated as continuous task
     *  killWhenNotActiveFor => optional, will kill the task if its not pushed its active state via @see Task::updateCheckTime
     *  activeProcessCount => optional, if present this many tasks will be run
     */
    public static function schedule();
}