<?php
date_default_timezone_set('PRC');
set_time_limit(0);
!defined('SERVER_PATH') && define('SERVER_PATH', dirname(__DIR__).DIRECTORY_SEPARATOR);
require_once SERVER_PATH . 'common/index.php';
require_once SERVER_PATH . 'vendor/autoload.php';
money\Config::load(SERVER_PATH . 'config/storage.php');

function output($code = 0, $msg = 'success', $data = [])
{
    $res = ['code' => $code, 'msg' => $msg, 'data' => $data];
    exit(json_encode($res, JSON_UNESCAPED_UNICODE));
}