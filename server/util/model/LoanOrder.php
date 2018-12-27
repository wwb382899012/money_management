<?php

namespace money\model;

class LoanOrder extends BaseModel {
	const REDIS_KEY_LOAN_ORDER_LOCK = 'loan_order_lock';
	const REDIS_KEY_LOAN_ORDER_NUM_KEY = 'loan_order_num_key';
	const ORDER_STATUS_WAITING = 0;
	const ORDER_STATUS_OPTED = 1;
	const ORDER_STATUS_REJECT = 2; // 审批驳回
	const ORDER_STATUS_REFUSE = 4; // 审批拒绝
	const ORDER_STATUS_ARCHIVE = 20;
	const LOAN_STATUS_UNPAID = 0;
	const LOAN_STATUS_PAYING = 1;
	const LOAN_STATUS_PAID = 2;
	const LOAN_STATUS_FAIL = 3;
	const LOAN_STATUS_REFUSE = 4;
	
	const REPAY_STATUS_WAITING = 1; //等待还款
	const REPAY_STATUS_REPAYING = 2 ; //还款中
	const REPAY_STATUS_PAIDED = 3 ; //还款结束
	protected $table = "m_loan_order";
	
    public $map = array(
        //指令状态
        'order_status'=>array(
            '0'=>'未处理',
            '1'=>'已处理',
            '2'=>'已驳回',
            '3'=>'归档完结'
        ),
        //借款状态
        'loan_status'=>array(
            '0'=>'未付款',
            '1'=>'付款中',
            '2'=>'已付款'
        ),
        //借款类型
        'order_loan_type'=>array(
        	'1'=>'借款',
        	'2'=>'提前还款',
        	'3'=>'延期还款'
        ),
    );
	/**
	 * 锁单，保证订单唯一性
	 */
	public function getLock() {
		$util = new \RedisUtil ( ENV_REDIS_BASE_PATH );
		$redisObj = $util->get_redis ();
		
		$system_flag = $this->params ['system_flag'];
		$out_order_num = $this->params ['out_order_num'];
		
		$ret = $redisObj->set ( self::REDIS_KEY_LOAN_ORDER_LOCK . $system_flag . '_' . $out_order_num, 1, [ 
				'nx',
				'ex' => 5 
		] );
		if (! $ret) {
			return false;
		}

		return $this->getCount(['system_flag' => $system_flag, 'out_order_num' => $out_order_num]) > 0 ? false : true;
	}
	
	/**
	 * 解锁
	 */
	public function unlock() {
		$util = new \RedisUtil ( ENV_REDIS_BASE_PATH );
		$redisObj = $util->get_redis ();
		
		$system_flag = $this->params ['system_flag'];
		$out_order_num = $this->params ['out_order_num'];
		
		return $redisObj->del ( self::REDIS_KEY_LOAN_ORDER_LOCK . $system_flag . '_' . $out_order_num );
	}
	public static function getOrderNum($main_body_code) {
		// return date('Ymd').$redisObj->incr(self::REDIS_KEY_PAY_ORDER_NUM_KEY);
		$util = new \RedisUtil ( ENV_REDIS_BASE_PATH );
		$redis = $util->get_redis ();
		
		$nday = date ( "Ymd" );
		$key = '02-IN-' . $main_body_code . '-' . $nday;
		$seqid = 0;
		if (! ($seqid = $redis->incr ( $key ))) {
			throw new \Exception ( '获取编号失败', \ErrMsg::RET_CODE_LOAD_ORDER_NUM_ERROR );
		}
		
		if ($seqid == 1) {
			$redis->expire ( $key, 86400 );
		}
		
		$t_seq = sprintf ( "%05d", $seqid );
		
		return '02-IN-' . $main_body_code . '-' . $nday . '-' . $t_seq;
	}
	
	// 获取订单详情列表
	public function orderDetails($params, $cols, $page, $pageSize) {
		$cols = $cols ? $cols : '*';
		
		$keys = [ 
				'system_flag',
				'order_num',
				'out_order_num',
				'order_status',
				'order_create_people',
				'loan_main_body_uuid',
				'collect_main_body_uuid',
				'loan_type',
				'loan_status' 
		];
		
		$where = [ ];
		foreach ( $params as $key => $val ) {
			if (in_array ( $key, $keys )) {
				$where [] = [ 
						$key,
						'=',
						$val 
				];
			}
		}
		if (! empty ( $params ['loan_main_body_uuids'] ) && is_array ( $params ['loan_main_body_uuids'] ) && count ( $params ['loan_main_body_uuids'] ) > 0) {
			$where [] = [ 
					'loan_main_body_uuid',
					'in',
					$params ['loan_main_body_uuids'] 
			];
		}
		if (! empty ( $params ['apply_begin_time'] )) {
			$where [] = [ 
					'create_time',
					'>=',
					$params ['apply_begin_time'] 
			];
		}
		if (! empty ( $params ['apply_end_time'] )) {
			$where [] = [ 
					'create_time',
					'<=',
					$params ['apply_end_time'] 
			];
		}
		if (! empty ( $params ['approve_begin_time'] )) {
			$where [] = [ 
					'update_time',
					'>=',
					$params ['approve_begin_time'] 
			];
		}
		if (! empty ( $params ['approve_end_time'] )) {
			$where [] = [ 
					'update_time',
					'<=',
					$params ['approve_end_time'] 
			];
		}
		return $this->getDatasByPage ( $where, $cols, $page, $pageSize );
	}
}
