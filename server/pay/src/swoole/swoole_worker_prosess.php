<?php

use money\model\PayOrder;
use money\model\SystemInfo;
use money\base\ParamsUtil;
use money\base\RSAUtil;
class swoole_worker_prosess{
    public function workerTimer($params){

    }

    public function workerStart($params){
    	
    }

    public function workerStop($server, $workerId){
		
    }

    public function taskStart($params){
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true); //开启异步信号处理（php7.1新特性），则在循环中无需调用pcntl_signal_dispatch函数
        }
        //注册信号量
        $this->registerSignal();

        $queue = $this->bindQueue();
    	$time = time();
    	$masTime = 600; //最多执行十分钟重启
        while(($time+$masTime) >time()){
            if (!function_exists('pcntl_async_signals')) {
                pcntl_signal_dispatch();//调用等待信号的处理器
            }

        	$dataObj = $queue->get();
        	if(!$dataObj){
        		sleep(60);
        		continue;
        	}
        	CommonLog::instance()->getDefaultLogger()->info('order notice begin|params:'.json_encode($dataObj->getBody()));
        	try{
        		$this->resultOpt(json_decode($dataObj->getBody(), true));
        		// 消息应答
        		$queue->ack($dataObj->getDeliveryTag());
        	}catch(\Exception $e){
        		CommonLog::instance()->getDefaultLogger()->error('监听消息发生错误', $e);
        		$queue->nack($dataObj->getDeliveryTag(),AMQP_REQUEUE);
        	}
        }
        exit;
    }

    public function taskStop($server, $workerId){

    }


    /**
     * 绑定队列
     */
    protected function bindQueue(){
        $amqp = new AmqpUtil();
        $amqp->declareExchange(ORDER_RESULT_EXCHANGE_NAME);
        $queue = $amqp->declareQueue('order_result#order_result_listener');
        $amqp->bind('order_result#order_result_listener', ORDER_RESULT_EXCHANGE_NAME, ORDER_RESULT_LISTENER);

        return $queue;
    }
    /**
     * system_flag
     * uuid
     * trade_type
     */
    protected function resultOpt($obj){
    	$keys = ['system_flag','trade_type','uuid'];
    	if(ParamsUtil::validateParams($obj, $keys)){
    		CommonLog::instance()->getDefaultLogger()->info('order result opt error|'.json_encode($obj));
    		return;
    	}
    	
    	$sys = SystemInfo::getSystemInfoByFlag($obj['system_flag']);
    	if(!$sys['notice_url']){
    		return;
    	}
    	
    	switch($obj['trade_type']){
    		case 1:
    			$cols = "out_order_num,order_num,"
            	."order_status,pay_status,(select name from m_sys_user u where u.username = m_pay_order.optor limit 1) optor,opt_msg,real_pay_date,"
            	."(select bank_name from m_bank_account b join m_pay_transfer f on f.pay_account_uuid=b.uuid where f.pay_order_uuid = m_pay_order.uuid) pay_bank_name,"
            	."(select pay_bank_account from m_pay_transfer f where f.pay_order_uuid = m_pay_order.uuid) pay_bank_account,"
            	."(select bank_water from m_pay_transfer f where f.pay_order_uuid = m_pay_order.uuid) bank_water";
            	$ret = PayOrder::getDataById($obj['uuid'],$cols);
    	    	
		    	if(!isset($ret['out_order_num']))
		    	{
		    		throw new Exception("查询结果为空" , ErrMsg::RET_CODE_SERVICE_FAIL);	
		    	}
		    	
		    	$ret['secret'] = $this->secretGet($ret,$sys['notice_key']);
		    	
	    		$u = new RSAUtil();
	    		$ret = $u->publicEncrypt(json_encode($ret),$sys['public_key']);

	    		$con = curl_init();
	    		curl_setopt($con,CURLOPT_URL,$sys['notice_url']);
	    		curl_setopt($con, CURLOPT_POST,true);
	    		curl_setopt($con, CURLOPT_RETURNTRANSFER,1);
	    		$params = [
		    		'cmd'=>'80010001',
		    		'data'=>$ret,
	    		];
	    		curl_setopt($con, CURLOPT_POSTFIELDS, json_encode($params));
	    		CommonLog::instance()->getDefaultLogger()->info('order notice begin|url:'.$sys['notice_url']);
	    		CommonLog::instance()->getDefaultLogger()->info('order notice begin|params:'.json_encode($params));
	    		$ret = curl_exec($con);
	    		CommonLog::instance()->getDefaultLogger()->info('order notice end|ret:'.$ret);
	    		$ret=  json_decode($ret , true);
	    		if(!isset($ret['code'])||$ret['code']!=0){
	    			throw new \Exception('接口回调错误');
	    		}
	    		curl_close($con);
    			return;
    		default :
    			break;
    	}
    }
    
    /**
     * 加密验证
     * @param $params
     * @param $secretKey
     * @return string
     */
    private function secretGet($params , $secretKey = 'aabb')
    {
    	ksort($params);
    	foreach($params as $key => $value) {
    		if (in_array($key, ['secret', 'sessionToken'])) {
    			continue;
    		}
    		$strs[] = $key . '=' . $value;
    	}
    	$str = implode('&' , $strs).$secretKey;
    	CommonLog::instance()->getDefaultLogger()->info('order notice end|secret:'.$str);
    	return sha1($str);
    }

    /**
     * 注册信号量
     */
    protected function registerSignal() {
        pcntl_signal(SIGQUIT, [$this, 'sigHandler']);
        pcntl_signal(SIGILL, [$this, 'sigHandler']);
        pcntl_signal(SIGUSR1, [$this, 'sigHandler']);
        pcntl_signal(SIGUSR2, [$this, 'sigHandler']);
        pcntl_signal(SIGTERM, [$this, 'sigHandler']);
    }

    /**
     * 信号处理函数
     * @param int $signal
     */
    protected function sigHandler($signal) {
        switch ($signal) {
            case SIGQUIT:
            case SIGILL:
            case SIGUSR1:
            case SIGUSR2:
            case SIGTERM:
                posix_kill(posix_getpid(),SIGKILL);
                break;
            default:
                break;
        }
    }
}
