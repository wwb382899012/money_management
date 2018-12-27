<?php
/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/7/20
 * Time: 10:54
 */


if (!defined('COMMON_PATH'))
{
    define('COMMON_PATH',__DIR__.DIRECTORY_SEPARATOR);
}

if (!defined('COMMON_UTIL_PATH'))
{
    define("COMMON_UTIL_PATH",COMMON_PATH."util".DIRECTORY_SEPARATOR);
}

if (!defined('COMMON_CONFIG_PATH'))
{
    define("COMMON_CONFIG_PATH",COMMON_PATH."config".DIRECTORY_SEPARATOR);
}

if (!defined('COMMON_LOG_UTIL_PATH'))
{
    define("COMMON_LOG_UTIL_PATH",COMMON_PATH."log_util".DIRECTORY_SEPARATOR);
}

require_once COMMON_CONFIG_PATH.'cmd.php';
require_once COMMON_CONFIG_PATH.'error.php';
require_once COMMON_CONFIG_PATH.'ErrMsg.php';
require_once COMMON_CONFIG_PATH.'key.php';
require_once COMMON_CONFIG_PATH.'AutoloadService.php';
require_once COMMON_UTIL_PATH.'AutoLoadClass.php';
require_once COMMON_LOG_UTIL_PATH.'CommonLog.php';

JmfUtil::envInit();

/**
 * TODO::优化错误信息
 */
function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    //Runtime_Info::instance()->getDefaultLogger()->error(sprintf("%d|%s:%s:%s",$errno,$errstr,$errfile,$errline));
    $acc_log = sprintf("%s|%s|%d|%d|%s",date('Y-m-d H:i:s'),'System',0,$errno,$errstr);
    //Runtime_Info::instance()->getAccLogger()->info($acc_log);
    CommonLog::instance()->getDefaultLogger()->error($acc_log);
    throw new Exception($errstr, $errno);
}


set_error_handler("myErrorHandler");