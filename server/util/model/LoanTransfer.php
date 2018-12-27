<?php
namespace money\model;

class LoanTransfer extends BaseModel{
	public $table = 'm_loan_transfer';
	
	const REDIS_KEY_LOAN_TRANSFER_NUM_KEY = 'loan_transfer_num_key';
	
    const TRANSFER_STATUS_WAITING = 0;
    const TRANSFER_STATUS_OPTED = 1;
    const TRANSFER_STATUS_COMFIRMED = 2;
    const TRANSFER_STATUS_REJECT = 3;
    const TRANSFER_STATUS_REFUSE = 4;
    const TRANSFER_STATUS_WAIT_MATSTER_OPT = 5;
    
    const TRANSFER_STATUS_WAIT_TICKET_BACK = 19; //待上传回单
    const TRANSFER_STATUS_ARCHIVE = 20;//已归档
	
    const LOAN_STATUS_UNPAID = 0;
    const LOAN_STATUS_PAYING = 1;
    const LOAN_STATUS_PAID = 2;
    const LOAN_STATUS_FAIL = 3;
    const LOAN_STATUS_UNCONFIRM = 10;
    
    const REPAY_STATUS_WAITING = 0;
    const REPAY_STATUS_OPTED = 1;
    const REPAY_STATUS_MASTER_CONFIRM = 2;
    const REPAY_STATUS_CONFIRMED = 3;
    const REPAY_STATUS_REJECT = 4;
    const REPAY_STATUS_WAIT_TICKET_BACK = 19;//待上传回单
    const REPAY_STATUS_ARCHIVE = 20;
	
	public $map = array(
			//指令状态
			'order_status'=>array(
					'0'=>'未处理',
					'1'=>'已处理',
					'2'=>'已驳回',
					'3'=>'归档完结'
			),
			//付款状态
			'loan_status'=>array(
					'0'=>'未付款',
					'1'=>'付款中',
					'2'=>'已付款'
			),
			//实付类型
			'real_pay_type'=>array(
					'1'=>'网银',
					'2'=>'银企直联',
			),
	);
	
	/**
	 *	获取调拨编号
	*/
	public static function getTransferNum($main_body_code) {
		// return date('Ymd').$redisObj->incr(self::REDIS_KEY_PAY_ORDER_NUM_KEY);
		$util = new \RedisUtil ( ENV_REDIS_BASE_PATH );
		$redis = $util->get_redis ();
		
		$nday = date ( "Ymd" );
		$key = '02-TR-' . $main_body_code . '-' . $nday;
		$seqid = 0;
		if (! ($seqid = $redis->incr ( $key ))) {
			throw new \Exception ( '获取编号失败', \ErrMsg::RET_CODE_LOAD_ORDER_NUM_ERROR );
		}
		
		if ($seqid == 1) {
			$redis->expire ( $key, 86400 );
		}
		
		$t_seq = sprintf ( "%05d", $seqid );
		
		return '02-TR-' . $main_body_code . '-' . $nday . '-' . $t_seq;
	}
	
	//获取调拨详情列表
	public function details($params , $cols , $page , $pageSize)
	{
		$cols = $cols?$cols:'*';
		$keys = ['transfer_status','order_create_people'
				,'o.loan_type','f.loan_status','repay_transfer_status','repay_status','order_num','f.loan_main_body_uuid','f.collect_main_body_uuid'];
		$where = [];
		foreach ($params as $key => $val) {
			if (in_array($key, $keys)) {
				$where[] = [$key, '=', $val];
			}
		}
		if(!empty($params['loan_main_body_uuids'])&&is_array($params['loan_main_body_uuids'])&&count($params['loan_main_body_uuids'])>0){
			$where[] = ['o.loan_main_body_uuid','in',$params['loan_main_body_uuids']];
		}
		if(!empty($params['collect_main_body_uuids'])&&is_array($params['collect_main_body_uuids'])&&count($params['collect_main_body_uuids'])>0){
			$where[] = ['o.collect_main_body_uuid','in',$params['collect_main_body_uuids']];
		}
		if(!empty($params['apply_begin_time'])){
			$where[] = ['f.create_time', '>=', $params['apply_begin_time']];
		}
		if(!empty($params['apply_end_time'])){
			$where[] = ['f.create_time', '<=', $params['apply_end_time']];
		}
		if(!empty($params['approve_begin_time'])){
			$where[] = ['f.update_time', '>=', $params['approve_begin_time']];
		}
        if(!empty($params['approve_end_time'])){
            $where[] = ['f.update_time', '<=', $params['approve_end_time']];
        }
        if(!empty($params['loan_begin_datetime'])){
            $where[] = ['f.loan_datetime', '>=', $params['loan_begin_datetime']];
        }
        if(!empty($params['loan_end_datetime'])){
			$where[] = ['f.loan_datetime', '<=', $params['loan_end_datetime']];
		}

		(!isset($page) || $page < 1) && $page = 1;
		!isset($pageSize) && $pageSize = 20;
		$result = ['page'=>$page, 'limit'=>$pageSize, 'count'=>0, 'data'=>[]];
		if ($pageSize&&$pageSize<0) {
			$result['data'] = $this->table('m_loan_transfer f')->join(" m_loan_order o "," o.uuid = f.loan_order_uuid ")->field($cols)->where($where)->order(['f.create_time' => 'desc'])->select()->toArray();
		} else {
			$count = $this->table('m_loan_transfer f')->join(" m_loan_order o "," o.uuid = f.loan_order_uuid ")->where($where)->count();
			if(!empty($count)){
				$result['count'] = $count;
				$result['data'] = $this->table('m_loan_transfer f')->join(" m_loan_order o "," o.uuid = f.loan_order_uuid ")->field($cols)->where($where)->order(['f.create_time' => 'desc'])->page($page, $pageSize)->select()->toArray();
			}
		}
		return $result;
	}
	
	public static function refuse($uuid){
		$tranData = LoanTransfer::getDataById($uuid);
	
		$order = new LoanOrder();
		$order->params['uuid'] = $tranData['loan_order_uuid'];
		$order->params['order_status'] = LoanOrder::ORDER_STATUS_REJECT;
		$order->update();
			
		$tran = new LoanTransfer();
		$tran->params['uuid'] = $uuid;
		$tran->params['transfer_status'] = LoanTransfer::TRANSFER_STATUS_REJECT;
		$tran->update();
	}
}

