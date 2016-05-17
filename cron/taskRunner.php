<?php
include 'base.php';

$will_check_continuous_task_for = 56;

$settings = \App\Helper\Container::getContainer()->get('settings')['taskRunner'];

if (!$settings['enabled']) {
    die("Task Component is not enabled");
}


if (isset($argv[1]) && $argv[1] == "runTask") {
    $class = $argv[2];
    $class = new $class;
    /* @var $class \App\Base\Task */
    $class->start();
} else {
    $run_tasks_at_the_end_of_minute = array();
    $run_continuous_tasks = array();
    $next_minute = "+1mins";


    $available_tasks = scanAvailableTasks();

    /* @var $available_task string */
    foreach ($available_tasks as $available_task) {
        if (!method_exists($available_task, 'schedule')) {
            continue;
        }

        $schedule = $available_task::schedule();

        if (!isset($schedule['activeProcessCount'])) {
            $schedule['activeProcessCount'] = 1;
        }

        //is timed task
        if (isset($schedule['runEvery'])) {
            if (isset($schedule['killWhenNotActiveFor'])) {
                checkRuntimeAndKill($available_task, $schedule['killWhenNotActiveFor'], $settings['redisInstanceName']);
            }

            if (!\Cron\CronExpression::isValidExpression($schedule['runEvery'])) {
                \App\Helper\Container::getLogger()->addInfo("Bad cron format Class $available_task cron_format " . $schedule['runEvery']);
                continue;
            }

            $cron_check = \Cron\CronExpression::factory($schedule['runEvery']);
            if ($cron_check->isDue($next_minute)) {

                for ($i = 0; $i < $schedule['activeProcessCount']; $i++) {
                    $run_tasks_at_the_end_of_minute[] = $available_task;
                }
            }

        } //is continuous task
        else {
            $run_continuous_tasks[$available_task] = $schedule;
        }
    }

    do {

        foreach ($run_continuous_tasks as $class_name => $schedule) {
            if (isset($schedule['killWhenNotActiveFor'])) {
                checkRuntimeAndKill($class_name, $schedule['killWhenNotActiveFor'], $settings['redisInstanceName']);
            }

            $output = exec("ps aux | grep taskRunner | grep -F '$class_name' | grep -v grep | wc -l");
            $runCount = $schedule['activeProcessCount'] - $output;

            for ($i = 0; $i < $runCount; $i++) {
                exec('(nohup ' . $settings['pathToPhp'] . ' -f ' . realpath(__DIR__) . '/taskRunner.php runTask "' . $class_name . '" > /dev/null 2>&1 ) & echo ${!};');
            }
        }
        sleep(1);

    } while (date("s") <= $will_check_continuous_task_for);

    if (!empty($run_tasks_at_the_end_of_minute)) {
        sleep(60 - date("s"));
        foreach ($run_tasks_at_the_end_of_minute as $class_name) {
            exec('(nohup ' . $settings['pathToPhp'] . ' -f ' . realpath(__DIR__) . '/taskRunner.php runTask "' . $class_name . '" > /dev/null 2>&1 ) & echo ${!};');
        }
    }

}


function checkRuntimeAndKill($class_name, $killWhenNotActiveFor, $redisInstanceName)
{
    exec("ps aux | grep taskRunner | grep -F '$class_name' | grep -v grep | awk {'print $2'}", $output);
    if (!empty($output)) {
        foreach ($output as $pid) {
            try {
                $last_check_time = \App\ConnectionManager\RedisManager::getInstance($redisInstanceName)->get("task:update_check_time:class:" . $class_name . "pid:" . $pid);

                $ts_kill_when_not_active_for = strtotime($killWhenNotActiveFor, 0);
                if (time() - $ts_kill_when_not_active_for > $last_check_time) {
                    exec("kill $pid");
                    \App\Helper\Container::getLogger()->addInfo("TaskRunner: class:$class_name pid:$pid Killed was running over $ts_kill_when_not_active_for seconds");
                }
            } catch (\Throwable $e) {
                \App\Helper\Container::getLogger()->addInfo($e);
            }
        }
    }
}

function scanAvailableTasks()
{
    $task_base_path = __DIR__ . "/../App/Task/";
    $task_class_files = array();
    foreach (scandir($task_base_path) as $task_class_file) {
        if (stripos($task_class_file, ".php") === false) {
            continue;
        }
        $task_class_file = '\\App\\Task\\' . substr($task_class_file, 0, -4);
        $task_class_files[] = $task_class_file;
    }

    return $task_class_files;
}