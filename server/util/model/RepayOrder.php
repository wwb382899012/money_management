<?php

namespace money\model;

class RepayOrder extends BaseModel
{
	protected $table = 'm_repay_order';
	protected $pk = 'id';

    const ORDER_STATUS_WAITING = 0;
    const ORDER_STATUS_OPTED = 1;
    const ORDER_STATUS_REJECT = 2; // 审批驳回
    const ORDER_STATUS_REFUSE = 4; // 审批拒绝
    const ORDER_STATUS_ARCHIVE = 20;
    const REPAY_STATUS_WAITING = 0;

    const REPAY_STATUS_OPTED = 1;    //审批通过
    const REPAY_STATUS_REJECT = 2;	 //审批驳回
    const REPAY_STATUS_REFUSE = 4; // 审批拒绝
    const REPAY_STATUS_ARCHIVE = 20;  //指令处理结束
    
    const REDIS_KEY_REPAY_ORDER_LOCK = 'repay_order_lock';
	
    public static function getLock($system_flag , $out_order_num)
	{
		$util = new \RedisUtil(ENV_REDIS_BASE_PATH);
		$redisObj = $util->get_redis();

// 		$system_flag = $this->params['system_flag'];
// 		$out_order_num = $this->params['out_order_num'];

		$i = 0;
		$r = $redisObj->set(self::REDIS_KEY_REPAY_ORDER_LOCK.$system_flag.'_'.$out_order_num, 1, ['nx', 'ex'=>5]);
		while(!$r&&$i<5){
			$r = $redisObj->set(self::REDIS_KEY_REPAY_ORDER_LOCK.$system_flag.'_'.$out_order_num, 1, ['nx', 'ex'=>5]);
			sleep(1);
			$i++;
		}
		if(!$r){
			throw new \Exception('系统锁定时间过长',\ErrMsg::RET_CODE_SERVICE_FAIL);
		}
		$sql = "select * from m_repay_order where system_flag = '$system_flag'
		and out_order_num = '$out_order_num'";
		$obj = new static();
		$res = $obj->query($sql);
	
		if(count($res)!=0){
			return $res[0];
		}
		return 1;
	}
	
	/**
	 *	解锁
	 */
	public static function unlock($system_flag , $out_order_num)
	{
		$util =  new \RedisUtil(ENV_REDIS_BASE_PATH);
		$redisObj = $util->get_redis();
	
// 		$system_flag = $this->params['system_flag'];
// 		$out_order_num = $this->params['out_order_num'];
	
		$ret = $redisObj->del(self::REDIS_KEY_REPAY_ORDER_LOCK.
				$system_flag.'_'.$out_order_num);
	}
	
	public static function getOrderNum($main_body_code) {
		// return date('Ymd').$redisObj->incr(self::REDIS_KEY_PAY_ORDER_NUM_KEY);
		$util = new \RedisUtil ( ENV_REDIS_BASE_PATH );
		$redis = $util->get_redis ();
		
		$nday = date ( "Ymd" );
		$key = '03-IN-' . $main_body_code . '-' . $nday;
		$seqid = 0;
		if (! ($seqid = $redis->incr ( $key ))) {
			throw new \Exception ( '获取编号失败', \ErrMsg::RET_CODE_LOAD_ORDER_NUM_ERROR );
		}
		
		if ($seqid == 1) {
			$redis->expire ( $key, 86400 );
		}
		
		$t_seq = sprintf ( "%05d", $seqid );
		
		return '03-IN-' . $main_body_code . '-' . $nday . '-' . $t_seq;
	}
	
	//获取订单详情列表
	public function orderDetails($params , $cols , $page , $pageSize){
		$cols = $cols?$cols:'*';
	
		$keys = ['system_flag','order_num','m.out_order_num','repay_order_status','m.order_create_people'
				,'m.repay_main_body_uuid','f.collect_main_body_uuid','o.order_num'];
	
		$where = [];
		foreach ($params as $key => $val) {
			if (in_array($key, $keys)) {
				$where[] = [$key, '=', $val];
			}
		}
	
		if(!empty($params['apply_begin_time'])){
			$where[] = ['m.create_time', '>=', $params['apply_begin_time']];
		}
		if(!empty($params['apply_end_time'])){
			$where[] = ['m.create_time', '<=', $params['apply_end_time']];
		}
		if(!empty($params['approve_begin_time'])){
			$where[] = ['m.update_time', '>=', $params['approve_begin_time']];
		}
		if(!empty($params['approve_end_time'])){
			$where[] = ['m.update_time', '<=', $params['approve_end_time']];
		}
		if(!empty($params['repay_main_body_uuids'])&&is_array($params['repay_main_body_uuids'])&&count($params['repay_main_body_uuids'])>0){
			$where[] = ['m.repay_main_body_uuid','in',$params['repay_main_body_uuids']];
		}	
		
		(!isset($page) || $page < 1) && $page = 1;
		!isset($pageSize) && $pageSize = 20;
		$result = ['page'=>$page, 'limit'=>$pageSize, 'count'=>0, 'data'=>[]];
		if ($pageSize&&$pageSize<0) {
			$result['data'] = $this->table('m_repay_order m')->join(" m_loan_transfer f "," f.uuid = loan_transfer_uuid ")->join(" m_loan_order o "," o.uuid = f.loan_order_uuid ")->field($cols)
			->where($where)->order(['create_time' => 'desc'])->select()->toArray();
		} else {
			$count = $this->table('m_repay_order m')->join(" m_loan_transfer f "," f.uuid = loan_transfer_uuid ")->join(" m_loan_order o "," o.uuid = f.loan_order_uuid ")->where($where)->count();
			if(!empty($count)){
				$result['count'] = $count;
				$result['data'] = $this->table('m_repay_order m')->join(" m_loan_transfer f "," f.uuid = loan_transfer_uuid ")->join(" m_loan_order o "," o.uuid = f.loan_order_uuid ")->field($cols)->where($where)->order(['m.create_time' => 'desc'])->page($page, $pageSize)->select()->toArray();
			}
		}
		return $result;
	}
}
