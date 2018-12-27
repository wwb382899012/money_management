<?php
/**
 * 内部调拨
 * @author sun
 *
 */
namespace money\model;

class InnerTransfer extends BaseModel
{
	protected $table = "m_inner_transfer";
	const TRANSFER_STATUS_SAVED = 0;  //已保存
	const TRANSFER_STATUS_WAITING = 1;   //待资金负责人审核
	const TRANSFER_STATUS_OPTED = 2;     //待权签人审核
	const TRANSFER_STATUS_COMFIRMED = 3;  //权签人审核通过
	const TRANSFER_STATUS_REJECT = 4;     //资金负责人驳回
	const TRANSFER_STATUS_CHECK_REJECT = 5;     //权签人人驳回
	const TRANSFER_STATUS_WAIT_TICKET_BACK = 19; //待上传回单
	const TRANSFER_STATUS_ARCHIVE = 20;   //已完结

	
	const INNER_STATUS_UNPAID = 0;
	const INNER_STATUS_PAYING = 1;
	const INNER_STATUS_PAID = 2;
	const INNER_STATUS_FAIL = 3;
	const INNER_STATUS_UNCONFIRM = 10;
	
	//获取详情列表
	public function details($params , $cols , $page , $pageSize){
		$cols = $cols?$cols:'*';
	
		$keys = ['main_body_uuid','transfer_status','pay_status'];
        $where = [];
		foreach ($params as $key => $val) {
			if (in_array($key, $keys)&&isset($val)) {
				$where[] = [$key, '=', $val];
			}
		}
        if(!empty($params['apply_begin_time'])){
            $where[] = ['create_time', '>=', $params['apply_begin_time']];
        }
        if(!empty($params['apply_end_time'])){
            $where[] = ['create_time', '<=', $params['apply_end_time']];
        }
        if(!empty($params['approve_begin_time'])){
            $where[] = ['update_time', '>=', $params['approve_begin_time']];
        }
        if(!empty($params['approve_end_time'])){
            $where[] = ['update_time', '<=', $params['approve_end_time']];
        }
        
        if(!empty($params['main_body_uuids'])&&is_array($params['main_body_uuids'])&&count($params['main_body_uuids'])>0){
        	$where[] = ['main_body_uuid','in',$params['main_body_uuids']];
        }
        return $this->getDatasByPage($where, $cols, $page, $pageSize);
	}
	
	public static function refuse($uuid){

		$obj = new static();
		return $obj->where(['uuid' => $uuid])->update(['transfer_status' => InnerTransfer::TRANSFER_STATUS_REJECT]);
	}
	
	public static function getOrderNum($main_body_code){
		//return date('Ymd').$redisObj->incr(self::REDIS_KEY_PAY_ORDER_NUM_KEY);
		$util = new \RedisUtil(ENV_REDIS_BASE_PATH);
		$redis = $util->get_redis();
	
		$nday = date("Ymd");
		$key = '04-TR-'.$main_body_code.'-'.$nday;
		$seqid = 0;
		if(!($seqid = $redis->incr($key)))
		{
			throw new \Exception('获取编号失败'
					,\ErrMsg::RET_CODE_LOAD_ORDER_NUM_ERROR);
		}
	
		if($seqid == 1)
		{
			$redis->expire($key, 86400);
		}
	
		$t_seq = sprintf("%05d", $seqid);
	
		return '04-TR-'.$main_body_code.'-'.$nday.'-'.$t_seq;
	}
	
}
