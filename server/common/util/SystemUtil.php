<?php
/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/6/22
 * Time: 12:06
 */


class SystemUtil
{
    private static $localHost = null;
    private static $defaultHost = '127.0.0.1';

    public static function getLocalHost()
    {
        if (empty(self::$localHost))
        {
            /*
             * TODO 后继需根据网段进行筛选
             */
            if (extension_loaded('swoole'))
            {
                $hostList = swoole_get_local_ip();
                foreach ($hostList as $key => $local)
                {
                    if (strncmp($local, self::$defaultHost, strlen(self::$defaultHost)) != 0)
                    {
                        self::$localHost = $local;
                        break;
                    }
                    else
                    {
                        self::$localHost = self::$defaultHost;
                    }
                }
            }
            else
            {
                self::$localHost = self::$defaultHost;
            }
        }
        return self::$localHost;
    }

    public static function getServerHost()
    {
        $result = self::getLocalHost();

        if ($result == self::$defaultHost)
        {
            throw new \Exception("get local host error");
        }

        return $result;
    }
}