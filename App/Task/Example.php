<?php
namespace App\Task;

use App\Base\Task;

class Example extends Task
{

    /**
     * @return bool
     */
    public function isActive() : bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isLogEnabled() : bool
    {
        return true;
    }

    protected function run()
    {
        sleep(10);
    }

    /**
     * @return array
     * return [
     * 'runEvery' => '* * * * *',
     * 'killWhenNotActiveFor' => '10mins',
     * 'activeProcessCount' => 2,
     * ];
     */
    public static function schedule()
    {
        return [
            'runEvery'             => '* * * * *',
            'killWhenNotActiveFor' => '10mins',
            'activeProcessCount'   => 2,
        ];
    }
}
