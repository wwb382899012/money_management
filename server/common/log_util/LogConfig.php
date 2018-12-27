<?php
/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/6/20
 * Time: 14:21
 */


//if(!defined('COMMON_DEFAULT_LOG')) define ('COMMON_DEFAULT_LOG', dirname(dirname(dirname(dirname(__DIR__)))).DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR);
if(!defined('COMMON_DEFAULT_LOG')) define ('COMMON_DEFAULT_LOG', 'default');

if(!defined('COMMON_INFO_LOG')) define('COMMON_INFO_LOG','info');
if(!defined('COMMON_ACC_LOG')) define('COMMON_ACC_LOG','acc');
if(!defined('COMMON_DB_LOG')) define('COMMON_DB_LOG','db');
if(!defined('COMMON_ERROR_LOG')) define('COMMON_ERROR_LOG','error');




class LogConfig
{
    public static $log_config = array();
    public static function getInstance()
    {
        if (!empty(self::$log_config))
        {
            return self::$log_config;
        }

        self::$log_config = array(
            'appenders' => array(
                COMMON_DEFAULT_LOG => array(
                    'class' => 'LoggerAppenderDailyFile',
                    'layout' => array(
                        'class' => 'LoggerLayoutPattern',
                        'params' => array('conversionPattern' => '%d{Y-m-d H:i:s.u}|%t|%X{SEQ}|%X{SPANID}|%p|%F|%M|%L|%m|%X{CALLCHAIN}%n%ex'),
                    ),
                    'params' => array(
                        'datePattern' => 'YmdH',
                        'file' => '../log/info_%s.log',
                    ),
                ),
                COMMON_ACC_LOG => array(
                    'class' => 'LoggerAppenderDailyFile',
                    'layout' => array(
                        'class' => 'LoggerLayoutPattern',
                        'params' => array('conversionPattern' => '%d{Y-m-d H:i:s.u}|%X{SEQ}|%m%n%ex'),
                    ),
                    'params' => array(
                        'datePattern' => 'YmdH',
                        'file' => '../log/acc_%s.log',
                    ),
                ),
                COMMON_DB_LOG => array(
                    'class' => 'LoggerAppenderDailyFile',
                    'layout' => array(
                        'class' => 'LoggerLayoutPattern',
                        'params' => array('conversionPattern' => '%d{Y-m-d H:i:s.u}|%X{SEQ}|%m%n%ex'),
                    ),
                    'params' => array(
                        'datePattern' => 'YmdH',
                        'file' => '../log/db_%s.log',
                    ),
                ),
                COMMON_ERROR_LOG => array(
                    'class' => 'LoggerAppenderDailyFile',
                    'layout' => array(
                        'class' => 'LoggerLayoutPattern',
                        'params' => array('conversionPattern' => '%d{Y-m-d H:i:s.u}|%t|%X{SEQ}|%X{SPANID}|%p|%F|%M|%L|%m|%X{CALLCHAIN}%n%ex'),
                    ),
                    'params' => array(
                        'datePattern' => 'YmdH',
                        'file' => '../log/error_%s.log',
                    ),
                ),
            ),
            'loggers' => array(
                COMMON_DEFAULT_LOG => array(
                    'level' => 'INFO',
                    'appenders' => array(COMMON_DEFAULT_LOG),
                ),
                COMMON_ACC_LOG => array(
                    'level' => 'INFO',
                    'appenders' => array(COMMON_ACC_LOG),
                ),
                COMMON_DB_LOG => array(
                    'level' => 'INFO',
                    'appenders' => array(COMMON_DB_LOG),
                ),
                COMMON_ERROR_LOG => array(
                    'level' => 'ERROR',
                    'appenders' => array(COMMON_ERROR_LOG),
                ),
            )
        );

        return self::$log_config;
    }
}