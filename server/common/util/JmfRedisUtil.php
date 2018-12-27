<?php

/**
 * Created by PhpStorm.
 * User: lewis.qing
 * Date: 2017/7/28
 * Time: 10:39
 */
class JmfRedisUtil
{
    private $_redis = null;
    private $_instance = null;



    const REQ_LIMIT_CNT = 10;
    const REQ_LIMIT_MSEC = 60000;
    const REQ_LIMIT_KEY = "reqlm_";

    /**
     * 基础服务的redis地址为：ENV_REDIS_SMS_CHECK_PATH
     * redis采用短连接模式
     */

    public function __construct($name)
    {
        //初始基础redis连接信息
        $this->initRedis($name);
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        if (isset($this->_redis))
        {
            $this->_redis->close();
            unset($this->_redis);
        }
    }

    public function initRedis($name)
    {
        $url = JmfUtil::getEnvConfig('ENV_REDIS_' . strtoupper($name));
        $this->_instance = new RedisUtil($url);
        $this->_redis = $this->_instance->get_redis();
    }

    /**
     * 限制访问次数
     * @param $ip：关键字标识码
     * @param $limit:固定标识码
     * @param $tm:时间范围
     * @return bool:设置成功，返回 1, 否则返回0
     */
    public function reqLimit($node, $limit=self::REQ_LIMIT_CNT, $tm=self::REQ_LIMIT_MSEC)
    {
        $key = self::REQ_LIMIT_KEY.$node;
        $ret = $this->setNx($key, 0);
        if($ret)
        {
            $this->trans($key, $tm);
        }
        else
        {
            $pttl = $this->getPttl($key);
            if($pttl < 0)
            {
                $resArr = $this->trans($key, $tm);
                if(empty($resArr) || $resArr[0] > $limit)
                    return false;
            }
            else
            {
                $num = $this->incr($key);
                if($num > $limit)
                    return false;
            }
        }

        return true;
    }


    public function deleteReqLimit($node)
    {
        $key = self::REQ_LIMIT_KEY.$node;
        $this->delete($key);
    }

    /**
     * 将 key 的值设为 value ，当且仅当 key 不存在
     * 若给定的 key 已经存在，则 SETNX 不做任何动作
     * @param $key
     * @param $value
     */
    public function setNx($key, $value)
    {
        return $this->_redis->setnx($key, $value);
    }

    /**
     * 将哈希表 key 中的域 field 的值设为 value
     * 如果 key 不存在，一个新的哈希表被创建并进行 HSET 操作
     * 如果域 field 已经存在于哈希表中，旧值将被覆盖
     *
     * @param $key
     * @param $field
     * @param $value
     * @return mixed:
     * 如果 field 是哈希表中的一个新建域，并且值设置成功，返回 1
     * 如果哈希表中域 field 已经存在且旧值已被新值覆盖，返回 0 。
     */
    public function hSet($key, $field, $value = 0)
    {
        return $this->_redis->hSet($key, $field, $value);
    }

    public function trans($key, $tm )
    {
        return $this->_redis->multi()->incr($key)->pexpire($key, $tm)->exec();
    }

    /**
     * 以毫秒为单位返回 key 的剩余生存时间
     * @param $key
     * @return mixed:
     * 当 key 不存在时，返回 -2 。
     * 当 key 存在但没有设置剩余生存时间时，返回 -1
     * 否则，以毫秒为单位，返回 key 的剩余生存时间。
     */
    public function getPttl($key)
    {
        return $this->_redis->pttl($key);
    }

    /**
     * 以秒为单位返回 key 的剩余生存时间
     * @param $key
     * @return mixed:
     * 当 key 不存在时，返回 -2
     * 当 key 存在但没有设置剩余生存时间时，返回 -1
     *
     */
    public function getTtl($key)
    {
        return $this->_redis->ttl($key);
    }

    /**
     * 将 key 中储存的数字值增一
     *
     * 如果 key 不存在，那么 key 的值会先被初始化为 0 ，然后再执行 INCR 操作
     * @param $key
     * @return mixed:执行 INCR 命令之后 key 的值
     */
    public function incr($key)
    {
        return $this->_redis->incr($key);
    }

    /**
     * 为哈希表 key 中的域 field 的值加上增量 increment
     * 增量也可以为负数，相当于对给定域进行减法操作
     * 如果 key 不存在，一个新的哈希表被创建并执行 HINCRBY 命令
     * 如果域 field 不存在，那么在执行命令前，域的值被初始化为 0
     *
     * @param $key
     * @param $field
     * @param $increment
     * @return mixed：执行 HINCRBY 命令之后，哈希表 key 中域 field 的值
     */
    public function hIncrBy($key, $field, $increment)
    {
        return $this->_redis->hIncrBy($key, $field, $increment);
    }

    /**
     * 返回哈希表 key 中，所有的域和值
     * @param $key
     * @return mixed:在返回值里，紧跟每个域名(field name)之后是域的值(value)，所以返回值的长度是哈希表大小的两倍
     */
    public function hGetAll($key)
    {
        return $this->_redis->hGetAll($key);
    }

    public function hGet($key, $value)
    {
        return $this->_redis->hGet($key, $value);
    }

    /**
     * 删除给定的一个或多个 key
     *
     * 不存在的 key 会被忽略
     * @param $key:
     * return 被删除 key 的数量
     */
    public function delete($key)
    {
        return $this->_redis->DEL($key);
    }

    /**
     * 为给定 key 设置生存时间，当 key 过期时(生存时间为 0 )，它会被自动删除。
     * 可以对一个已经带有生存时间的 key 执行 EXPIRE 命令，新指定的生存时间会取代旧的生存时间。
     *
     * @param $key
     * @param $timeot
     */
    public function expire($key, $tm)
    {
        $this->_redis->expire($key, $tm);
    }

    /**
     * 将字符串值 value 关联到 key
     * 如果 key 已经持有其他值， SET 就覆写旧值，无视类型
     * 对于某个原本带有生存时间（TTL）的键来说， 当 SET 命令成功在这个键上执行时， 这个键原有的 TTL 将被清除
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value )
    {
        $this->_redis->set($key, $value);
    }

    /**
     * 将值 value 关联到 key ，并将 key 的生存时间设为 seconds (以秒为单位)。
     * 如果 key 已经存在， SETEX 命令将覆写旧值。
     *
     * @param $key
     * @param $ttl
     * @param $value
     */
    public function setEx( $key, $ttl, $value )
    {
        $this->_redis->setex($key, $ttl, $value);
    }

    /**
     * 将值 value 关联到 key ，并将 key 的生存时间设为 milliseconds  (以毫秒为单位)。
     * 如果 key 已经存在， pSetEx 命令将覆写旧值。
     *
     * @param $key
     * @param $ttl
     * @param $value
     */
    public function pSetEx( $key, $ttl, $value )
    {
        $this->_redis->psetex($key, $value);
    }

    /**
     * 返回 key 所关联的字符串值
     * 假如 key 储存的值不是字符串类型，返回一个错误，因为 GET 只能用于处理字符串值
     *
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->_redis->get($key);
    }
}