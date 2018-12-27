<?php
/**
 * 延时通知
 * User: michael
 * Date: 2017/12/28
 * Time: 下午3:10
 */
class DelayNotifyUtil
{
    const CMD_DELAY = '14010000';
    public static function setDelay($msg, $queue, $wait_seconds, $tag='')
    {
        $req = array(
            "cmd"          => self::CMD_DELAY,
            "tag"          => $tag,
            "message"      => $msg,
            "queue_name"   => $queue,
            "wait_seconds" => $wait_seconds,
        );
        $url = JmfUtil::getEnvConfig('ENV_DELAY_' . strtoupper("NOTIFY"));
        $ret = JmfRequestLogic::requestLogic($url, $req);
        CommonLog::instance()->getDefaultLogger()->error("do DelayNotifyUtil setDelay| req=".StringUtil::json_encode_ex($req)."|ret=".StringUtil::json_encode_ex($ret));
        return $ret;
    }
}