<?php

/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/9/22
 * Time: 10:53
 */
class JmfRequestLogic
{
    public static function requestLogic($hosts,$msg, $args=array(), $timeout=3, $connectTimeout = 1)
    {
        if (empty($GLOBALS['seq__']))
        {
            $GLOBALS['seq__'] = '1234567890abcdefghijklmnopqrstuvwxyz';
        }
        $path['seq__'] = $GLOBALS['seq__'];
        $path['version'] = '3.0.0';
        $path['platform'] = 'ios';
        if(!empty($args))
        {
            foreach($args as $key=>$value)
            {
                $path[$key] = $value;
            }
        }

        $url = $hosts.'?'.http_build_query($path);

        $msg['seq__'] = $GLOBALS['seq__'];
        $msg['__seq'] = $GLOBALS['seq__'];
        $data = json_encode($msg);

        $http = new HttpRequestHandler();
        $http->setTimeout($timeout, $connectTimeout);
        $http->setHeader('Content-Type','application/json');
        $response = $http->post($url, $data);
        $ret = json_decode($response,TRUE);
        if (empty($ret) || empty($ret[PARAM_CODE]) || 0 != $ret[PARAM_CODE])
        {
            if (empty($ret[PARAM_MSG]))
            {
                $ret[PARAM_MSG] = '系统繁忙</br>请稍后尝试';
            }
            if (empty($ret))
            {
                $ret[PARAM_CODE] = '406';
            }
        }

        return $ret;
    }
}