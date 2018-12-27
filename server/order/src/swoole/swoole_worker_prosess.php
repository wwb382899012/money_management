<?php

use money\console\Schedule;

class swoole_worker_prosess extends Schedule
{
    public function __construct()
    {
        $this->appName = basename(dirname(dirname(__DIR__)));
        parent::__construct();
    }
}
