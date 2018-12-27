<?php

/**
 * Class TradeWater
 */
namespace money\model;

class SysTradeWater extends BaseModel
{
    const STATUS_EFFECT = 1;

    const STATUS_UNEFFECT = 2;
    const STATUS_WAIT_PAY = 1;

    const STATUS_PAYING = 2;
    const STATUS_SUCCESS = 3;
    const STATUS_FAIL = 4;
    const STATUS_WAIT_CONFIRM = 5;

    const REDIS_KEY_SYS_TRADE_WATER_LOCK = 'sys_trade_water_lock';
    protected $table = 'm_sys_trade_water';
   
    /**
     * 添加交易流水
     */
    public function addWater($data){
        $data['uuid'] = md5(uuid_create());
        $data['create_time'] = date('Y-m-d H:i:s');
        $res = $this->insert($data);
        return $res ? $data['uuid'] : null;
    }

    /** 
     * 更新交易流水 
     */
    public function saveWater($data, $uuid){
        return $this->where(['uuid' => $uuid, 'is_effective' => 1])->update($data);
    }

    public static function getApplyId($pre='SWT'){
        //return date('Ymd').$redisObj->incr(self::REDIS_KEY_PAY_ORDER_NUM_KEY);
        $util = new \RedisUtil(ENV_REDIS_BASE_PATH);
        $redis = $util->get_redis();

        $nday = date("Ymd");
        if($pre)
            $key = $pre.$nday;
        else
            $key = $nday;
        $seqid = 0;
        if(!($seqid = $redis->incr($key)))
        {
            throw new \Exception('获取借款编号失败'
                ,\ErrMsg::RET_CODE_LOAD_ORDER_NUM_ERROR);
        }

        if($seqid == 1)
        {
            $redis->expire($key, 86400);
        }

        $t_seq = sprintf("%07d", $seqid);

        return $nday.$t_seq;
    }

    public function getLock(){
        $util = new \RedisUtil(ENV_REDIS_BASE_PATH);
        $redisObj = $util->get_redis();

        $order_uuid = $this->params['order_uuid'];

        if (!$redisObj->set(self::REDIS_KEY_SYS_TRADE_WATER_LOCK.$order_uuid, 1, ['nx', 'ex'=>5])) {
            return false;
        }

        $res = $this->getOne(['order_uuid' => $order_uuid, 'status' => [self::STATUS_PAYING, self::STATUS_SUCCESS]], 'uuid');

        if(!empty($res)){
            return $res;
        }
        return true;
    }

    /**
     *	解锁
     */
    public function unLock(){
        $util =  new \RedisUtil(ENV_REDIS_BASE_PATH);
        $redisObj = $util->get_redis();

        $order_uuid = $this->params['order_uuid'];
        return $redisObj->del(self::REDIS_KEY_SYS_TRADE_WATER_LOCK.$order_uuid);
    }
    
    public static function confirmStatus($order_uuid,$status){
    	$obj = new SysTradeWater();
    	$data = $obj->loadDatas(['order_uuid'=>$order_uuid,'status'=>SysTradeWater::STATUS_WAIT_CONFIRM]);
    	
    	if(!is_array($data)||count($data)==0){
    		throw new \Exception('data not exists',\ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
    	}
    	
    	$status = $status==1?SysTradeWater::STATUS_SUCCESS:SysTradeWater::STATUS_FAIL;
    	$obj->params = [
	    	'uuid'=>$data[0]['uuid'],
	    	'status'=>$status
    	];
    	$obj->saveOrUpdate();
    }
}
