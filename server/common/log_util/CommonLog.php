<?php
/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/6/20
 * Time: 14:20
 */


if (!defined('SWOOLE_CONFIG_LOG4PHP'))
{
    define('SWOOLE_CONFIG_LOG4PHP',1);
    require_once('log4php/Logger.php');
}

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'LogConfig.php');


class CommonLog
{
    private static $_instance;

    private $m_log_path = null;
    private $m_app_name = null;


    public static function instance()
    {
        if ( ! isset(self::$_instance))
        {
            self::$_instance = new CommonLog();
        }
        return self::$_instance;
    }

    private function __construct()
    {
        date_default_timezone_set('PRC');
        $this->initLogger();
        $this->setTraceId();
    }

    public function setMDCKey($key, $value)
    {
        LoggerMDC::put($key, $value);
    }

    // 描述: 获取MDC参数值
    // 参数:
    //   [in] string $key  参数键值
    // 返回值:
    //    存储在MDC中的$key对应的$value值
    //
    public function getMDCKey($key)
    {
        return LoggerMDC::get($key);
    }
    private function setTraceId()
    {
        if ($this->getMDCKey('TRACEID'))
        {
            return;
        }

        $traceId = $this->generateTraceId();

        $this->setMDCKey('TRACEID',$traceId);
    }

    private function generateTraceId()
    {
        $localIp = SystemUtil::getLocalHost();
        $longIp = ip2long($localIp);

        $pid = getmypid();
        $curTime = microtime(true);//取到微秒
        $second = (int)$curTime;
        $ms = (int)(($curTime-$second)*1000);
        $rand = mt_rand(100000,999999);
        $msg = sprintf("%08X%010d%03d%06d%05X",$longIp,$second,$ms,$rand,$pid);
        return $msg;
    }

    private function initLogger()
    {
        Logger::configure(LogConfig::getInstance());
    }

    public function getDefaultLogger(){
        return  Logger::getLogger("default");
    }

    public function getAccLogger(){
        return  Logger::getLogger("acc");
    }

    public function getDbLogger()
    {
        return  Logger::getLogger("db");
    }




    private function initLog($ipAddr = null, $port = 0)
    {

        if (isset($ipAddr))
        {
            $localIp = $ipAddr.':'.$port;
        }
        else
        {
            $localIp = SystemUtil::getLocalHost();
        }
        \LoggerMDC::put("CALLCHAIN",$localIp);
    }

    //provider处理完请求后清理上下文，apache运行的consumer不需要调，apache进程自动会清理结构所有信息。
    //脚本在while循环内，处理完一次请求就需要 调用 一次该函数，否则每次请求都会使用相同的traceid
    public function clearTraceContext()
    {
        $this->m_trace_context = NULL;
        $this->m_rpc_id = 0;
        //\LoggerMDC::clear();
        \LoggerMDC::remove("TRACEID");
        \LoggerMDC::remove("SPINID");
        \LoggerMDC::remove("CALLCHAIN");

        return true;
    }

    public function setAppName($app_name)
    {
        $this->m_app_name = $app_name;
    }


    public function setLogPath($logPath)
    {
        $this->m_log_path = rtrim($logPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    }

    // 默认指向/home/product/logs/jmf_appname/frame
    public function getLogPath()
    {
        return $this->m_log_path.'jmf_'.$this->m_app_name.DIRECTORY_SEPARATOR.'frame'.DIRECTORY_SEPARATOR;
    }

}