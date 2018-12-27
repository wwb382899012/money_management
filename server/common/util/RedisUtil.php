<?php

class RedisUtil
{
    private static $_instance;

    const DEFALUT_TIMEOUT = 0.01;
	private $m_save_path='';
	private $m_redis = null;
	public function __construct($redisPath)
	{
		$this->m_save_path = $redisPath;
	}

	/*
    public static function instance($redisPath = null)
    {
        if (empty($redisPath))
        {
            throw new \Exception("配置异常",5000020009);
        }
        if (extension_loaded('redis'))
        {
            if (!isset(self::$_instance))
            {
                self::$_instance = new RedisUtil($redisPath);
            }
            return self::$_instance;
        }
        else
        {
            //JmfLog::instance()->getRegisterAddressLogger()->error("not installed redis extension");
        }
        return null;
    }
*/

	public function get_redis()
    {
		if(!$this->m_redis)
		{
			$config = $this->get_redis_config();
            if(!isset($config['ip']) || !isset($config['port'])){
                throw new Exception('error redis conf,no ip/port|save_path'.$this->m_save_path);
            }

            if(!isset($config['timeout'])){
                $config['timeout'] = self::DEFALUT_TIMEOUT;
            }
            if(!isset($config['password'])){
                $config['password'] = '';
            }

            if(isset($config['service']) && extension_loaded("f24k")){
                $redis_cli = new RedisF24kUtil();
                $connect_ret = $redis_cli->connect($config['service'],$config['timeout'],$config['ip'],$config['port'],$config['password']);
            }
            else{
                $redis_cli = new Redis();
                $connect_ret = $redis_cli->connect($config['ip'],$config['port'],$config['timeout']);  //php客户端设置的ip及端口
                if ($connect_ret && StringUtil::getStrParam($config,'password')!= '') {
                    $auth_ret = $redis_cli->auth($config['password']);
                    if (!$auth_ret)
                    {
                        //出于数据脱敏考虑，日志中只出现密码后三位，方便定位问题
                        Runtime_info::instance()->getDefaultLogger()->error("redis auth failed|password=****".substr($config['password'],-1,3));
                    }
                }
            }

            if(!$connect_ret){
                CommonLog::instance()->getDefaultLogger()->error('connect redis error!ip='.$config['ip'].'|port='.$config['port']);
                throw new Exception('connect redis error!ip='.$config['ip'].'|port='.$config['port']);
            }
            
            $this->m_redis = $redis_cli;
		}

		return $this->m_redis;
	}

    private function get_redis_config()
    {
        //'tcp://192.168.4.81:6379?weight=1&timeout=1,tcp://192.168.4.81:6379?weight=2&timeout=1'
        $config_arr = explode(',',$this->m_save_path);

        if(empty($config_arr))
        {
            throw new Exception('error redis conf,config_arr is null!');
        }
        //解析
        $configs = array();

        foreach ($config_arr as $config)
        {
            if (empty($config))
                continue;

            $url = new UrlUtil($config);
            $host = $url->getHost();
            $port = $url->getPort();
            $path = $url->getPath();
            $query = $url->getParameters();
            if(!isset($host) || !isset($port) || !isset($query))
            {
                throw new Exception('error redis conf,invalid url  format!');
            }

            $cfg = array('ip'=>$host, 'port'=>$port);
            if(isset($path)){
                $cfg['service'] = ltrim($path, '/');
            }

            $cfg = array_merge($cfg,$query);
            $configs[] = $cfg;

            /*
            $tmp = parse_url($config);
            if(!isset($tmp['host']) || !isset($tmp['port']) || !isset($tmp['query'])){
                throw new Exception('error redis conf,invalid url  format!');
            }
            $cfg = array('ip'=>$tmp['host'], 'port'=>$tmp['port']);
            if(isset($tmp['path'])){
                $cfg['service'] = ltrim($tmp['path'], '/');
            }

	        $paras = null;
			parse_str(trim($tmp['query']),$paras);
		    $cfg = array_merge($cfg,$paras);
		    $configs[] = $cfg;
            */
		}

        //随机选择
        $sum = 0;
        foreach($configs as $config){
            $sum+=StringUtil::getIntParam($config,'weight');
        }

        if ($sum >= 1) {
            $choice = mt_rand(1, $sum);
            $begin_sum = 0;
            foreach($configs as $config){
                $begin_sum+=StringUtil::getIntParam($config,'weight');
                if($begin_sum>=$choice){
                    return $config;
                }
            }
        }

        throw new Exception('error redis conf'.$this->m_save_path.' sum='.$sum);
    }


	/**
	 * Description: 关闭redis连接
	 */
	public function close(){
		if($this->m_redis){
			try {
				$this->m_redis->close();
			} catch (Exception $e) {
			}

			$this->m_redis= null;
		}
	}



	public function __destruct(){
		$this->close();
	}
}