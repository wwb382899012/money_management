<?php
/**
*	流程实例表
*	@author sun
*	@since 2018-03-19
*/

namespace money\model;

class SysAuditFlowInstance extends BaseModel
{
	public $table = 'm_sys_audit_instance';
	const REDIS_KEY_FLOW_INSTANCE_LOCK = 'flow_instance_lock';

	const FLOW_STATUS_WAITING = 1;
	const FLOW_STATUS_APPROVED = 2;
	const FLOW_STATUS_REFUSED = 3;


	/**
	*	锁单，保证订单唯一性
	*/
    public function getLock()
    {
        $flow_uuid = $this->params['flow_uuid'];
        $instance_id = $this->params['instance_id'];

        $r = new \RedisUtil(ENV_REDIS_BASE_PATH);
        $redisObj = $r->get_redis();

        if (!$redisObj->set(self::REDIS_KEY_FLOW_INSTANCE_LOCK.$flow_uuid.'_'.$instance_id, 1, ['nx', 'ex'=>5])) {
            return false;
        }

        return $this->getCount(['flow_uuid' => $flow_uuid, 'instance_id' => $instance_id, 'flow_instance_status' => self::FLOW_STATUS_WAITING]) > 0 ? false : true;
    }

	/**
	*	解锁
	*/
	public function unlock()
	{
		$flow_uuid = $this->params['flow_uuid'];
		$instance_id = $this->params['instance_id'];
		$r = new \RedisUtil(ENV_REDIS_BASE_PATH);
		$redisObj = $r->get_redis();
        return $redisObj->del(self::REDIS_KEY_FLOW_INSTANCE_LOCK.$flow_uuid.'_'.$instance_id);
	}


	/**
	*	流程实例信息
	*/
	public static function getDataByInstId($flow_uuid,$instance_id)
	{
		$array = array(
        	'flow_uuid'=>$flow_uuid,
            'instance_id'=>$instance_id,
            'flow_instance_status'=>'1'
        );
        $obj = new SysAuditFlowInstance();
        $res = $obj->loadDatas($array);
        if(!is_array($res)||count($res)==0)
             return null;
        return $res[0];
	}
}
