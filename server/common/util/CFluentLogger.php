<?php
/**
 * Fluent 封装
 */

require_once 'fluent/Fluent/Autoloader.php';

use Fluent\Logger\FluentLogger;

Fluent\Autoloader::register();

class CFluentLogger
{
    //发送日志到td-agent
    public function post($tag, $message)
    {
        $logger = new FluentLogger("unix:///var/run/td-agent/td-agent.sock");
        $logger->post("monitor." . $tag, $message);
    }

}
