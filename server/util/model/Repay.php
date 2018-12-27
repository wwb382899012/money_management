<?php

namespace money\model;

class Repay extends BaseModel
{
	protected $table = 'm_repay';
	protected $pk = 'id';
	const CODE_REPAY_STATUS_WATING = 0;
	const CODE_REPAY_STATUS_PAYING = 1;
	const CODE_REPAY_STATUS_SUCC = 2;
	const CODE_REPAY_STATUS_FAIL = 3;
	const CODE_REPAY_STATUS_UNCONFIRM = 10;
	const CODE_REPAY_TRANSFER_STATUS_WAITING = 0;
	const CODE_REPAY_TRANSFER_STATUS_OPTED = 1;
	const CODE_REPAY_TRANSFER_STATUS_MASTER_OPTED = 2;
	const CODE_REPAY_TRANSFER_STATUS_CONFIRMED = 3;
	const CODE_REPAY_TRANSFER_STATUS_REJECT = 4;
	const CODE_REPAY_TRANSFER_STATUS_ARCHIVE = 20;
	
	
	const CODE_EDIT_NOT_EXISTS_ORDER = 1;
	const CODE_EDIT_ORDER_APPROVEING = 2;
	const CODE_EDIT_WAIT_EIDT = 3;
	const CODE_EDIT_WAIT_APPROVE = 4;
	
	public static function getOrderNum($main_body_code) {
		// return date('Ymd').$redisObj->incr(self::REDIS_KEY_PAY_ORDER_NUM_KEY);
		$util = new \RedisUtil ( ENV_REDIS_BASE_PATH );
		$redis = $util->get_redis ();
		
		$nday = date ( "Ymd" );
		$key = '03-TR-' . $main_body_code . '-' . $nday;
		$seqid = 0;
		if (! ($seqid = $redis->incr ( $key ))) {
			throw new \Exception ( '获取编号失败', \ErrMsg::RET_CODE_LOAD_ORDER_NUM_ERROR );
		}
		
		if ($seqid == 1) {
			$redis->expire ( $key, 86400 );
		}
		
		$t_seq = sprintf ( "%05d", $seqid );
		
		return '03-TR-' . $main_body_code . '-' . $nday . '-' . $t_seq;
	}
	public static function pay($uuid, $jmgPassWord, $sessionToken, $repay) {
		$tran = new LoanTransfer ();
		$tranInfo = $tran->getDataById ( $uuid );
		
		if (! isset ( $jmgPassWord )) {
			throw new \Exception ( 'u盾密码不能为空', \ErrMsg::RET_CODE_DATA_VALIDATE_ERROR );
		}
		
		$ret = \JmfUtil::call_Jmf_consumer ( "com.jyblife.logic.bg.layer.SessionGet", array (
				'sessionToken' => $sessionToken 
		) );
		if (! isset ( $ret ['code'] ) || $ret ['code'] != 0 || empty ( $ret ['data'] )) {
			throw new \Exception ( '无法获取用户信息', \ErrMsg::RET_CODE_DATA_VALIDATE_ERROR );
		}
		$userInfo = $ret ['data'];
		
		$obj = new BankAccount ();
		$repay_account = $obj->getDataById ( $repay ['repay_account_uuid'] );
		$collect_account = $obj->getDataById ( $repay['collect_account_uuid'] );
		
		$payInfo = array (
				'trade_type' => 16,
				'order_uuid' => $repay ['repay_transfer_num'],
				'pay_bank_account' => $repay_account ['bank_account'],
				'collect_bank_account' => $collect_account ['bank_account'],
				'to_name' => $collect_account ['account_name'],
				'to_bank' => $collect_account ['bank_dict_key'],
				'to_bank_desc' => $collect_account ['bank_name'],
				'to_city_name' => $collect_account ['city'],
				'to_bank_num'=>$collect_account['bank_link_code'],
				'notice_url' => 'com.jyblife.logic.bg.loan.RepayNoticeResult',
				'jmgUserName' => $userInfo ['username'],
				'jmgPassWord' => $jmgPassWord,
				'amount' => $repay['amount'],
                'pay_remark' => $repay['pay_remark'],
		);
		
		$ret = \JmfUtil::call_Jmf_consumer ( "com.jyblife.logic.bg.pay.Order", $payInfo );
		if (isset ( $ret ['code'] ) && $ret ['code'] != 0) {
			$repay = new Repay ();
			$repay->params = [
				'id'=>$tranInfo ['cur_repay_id'],
				'repay_water_uuid' => $ret ['data'] ['uuid'],
				'repay_status'=>Repay::CODE_REPAY_STATUS_UNCONFIRM
			];
			$repay->saveOrUpdate ();
			
		}else{
			$repay = new Repay ();
			$repay->params = [ 
					'id' => $tranInfo ['cur_repay_id'],
					'repay_water_uuid' => $ret ['data'] ['uuid'] 
			];
			$repay->saveOrUpdate ();
		}
	}
	public static function setSucc($id,$bank_water=null) {
		/**
		 * 1、更新现金流水表
		 * 2、更新借款调拨表
		 * 3、更新还款明细表
		 */
		$repayInfo = Repay::getDataById ( $id );
		$loanInfo = LoanTransfer::getDataById ( $repayInfo ['loan_transfer_uuid'] );
		
		//是否有下期还款
		$f = new LoanCashFlow();
		$loan_cash_flows = $f->loadDatas(['loan_transfer_uuid'=>$repayInfo ['loan_transfer_uuid'],'cash_flow_type'=>2,'cash_status'=>LoanCashFlow::STATUS_WAITING,'index'=>(intval($repayInfo['index'])+1)]);
		$amount = 0;
		foreach($loan_cash_flows as $l){
			$amount+= $l['real_amount']; 
		}
		
		$loan = new LoanTransfer ();
		if (!is_array($loan_cash_flows)||count($loan_cash_flows)==0) {
			$loan->params = [ 
					'uuid' => $repayInfo ['loan_transfer_uuid'],
					'is_pay_off' => 2
			];
		} else {
			//未还清插入新数据
			$repay = new Repay();
			$mainBody = MainBody::getDataById($loanInfo['collect_main_body_uuid']);
			$r_params = [
				'repay_status'=>Repay::CODE_REPAY_STATUS_WATING,
				'repay_transfer_status'=>Repay::CODE_REPAY_TRANSFER_STATUS_WAITING,
				'loan_transfer_uuid'=>$loanInfo['uuid'],
				'repay_transfer_num'=>Repay::getOrderNum($mainBody['short_code']),
				'repay_main_body_uuid'=>$repayInfo['repay_main_body_uuid'],
				'collect_main_body_uuid'=>$repayInfo['collect_main_body_uuid'],
				'repay_account_uuid'=>$repayInfo['repay_account_uuid'],
				'collect_account_uuid'=>$repayInfo['collect_account_uuid'],
				'index'=>intval($repayInfo['index']) + 1,
				'currency'=>$loanInfo['currency'],
				'amount'=>$amount,
				'forecast_date'=>$loan_cash_flows[0]['repay_date'],
			];
			$repay->params = $r_params;
			$repay_id = $repay->saveOrUpdate();
			$loan->params = [ 
					'uuid' => $repayInfo ['loan_transfer_uuid'],
					'cur_repay_id' => $repay_id
			];
			
			$r = [
				'main_body_uuid'=>$mainBody['uuid'],
				'order_create_time'=>date('Y-m-d H:i:s'),
				'limit_date'=>$loan_cash_flows[0]['repay_date'],
				'opt_uuid'=>$repay_id,
				'trade_type'=>6
			];
			EodTradeDb::dataCreate($r);
		}
		
		$loan_cash_flows = $f->loadDatas(['loan_transfer_uuid'=>$repayInfo ['loan_transfer_uuid'],'index'=>$repayInfo['index']]);
		foreach($loan_cash_flows as $l){
			$f->params = [
				'uuid'=>$l['uuid'],
				'cash_status'=>LoanCashFlow::STATUS_PAID,
				'real_repay_date'=>date('Y-m-d')
			];
			$f->saveOrUpdate();
		}
		
		$loan_cash_flows = $f->loadDatas(['loan_transfer_uuid'=>$repayInfo ['loan_transfer_uuid'],'cash_status'=>LoanCashFlow::STATUS_PAID]);
		$repay_capital = 0;
		$repay_interest = 0;
		foreach($loan_cash_flows as $l){
			if($l['cash_flow_type']==2){
				$repay_capital += $l['real_amount'];
			}else if($l['cash_flow_type']==3){
				$repay_interest += $l['real_amount'];
			}
		}
		$loan->params ['repay_capital'] = $repay_capital;
		$loan->params ['repay_interest'] = $repay_interest;
		$loan->saveOrUpdate ();
		
		$repay = new Repay ();
		$repay->params = [ 
				'id' => $id,
				'repay_transfer_status' => Repay::CODE_REPAY_TRANSFER_STATUS_ARCHIVE,
				'repay_status' => Repay::CODE_REPAY_STATUS_SUCC,
				'bank_water'=>$bank_water
		];
		
		
		if ($repayInfo ['real_pay_type'] == 1) {
			$repay->params ['need_repay_ticket_back'] = 1;
		}
		$repay->saveOrUpdate ();
		EodTradeDb::dataOpted($id, 6);
		
		$obj = new ReportFullTrade();
		$obj->saveData(5,$repayInfo['repay_transfer_num']);
	}
	public static function setFail($id) {
		/**
		 * 1、更新现金流水表
		 * 2、更新借款调拨表
		 * 3、更新还款明细表
		 */
		$repayInfo = Repay::getDataById ( $id );
// 		$water = new SysTradeWater ();
// 		$waters = $water->loadDatas ( [ 
// 				'order_uuid' => $repayInfo ['repay_transfer_num'] 
// 		] );
// 		foreach ( $waters as $w ) {
// 			$water->params = [ 
// 					'uuid' => $w ['uuid'],
// 					'status' => SysTradeWater::STATUS_FAIL 
// 			];
// 			$water->saveOrUpdate ();
// 		}
		
		$repay = new Repay ();
		$repay->params = [ 
				'id' => $id,
				'repay_status' => Repay::CODE_REPAY_STATUS_FAIL
		];
		$repay->saveOrUpdate ();
// 		$r = new RepayCashFlow ();
// 		$repayCashInfos = $r->loadDatas ( [ 
// 				'repay_id' => $id 
// 		] );
// 		foreach ( $repayCashInfos as $rci ) {
// 			$r->params = [ 
// 					'id' => $rci ['id'],
// 					'status' => RepayCashFlow::STATUS_FAIL 
// 			];
// 			$r->saveOrUpdate ();
// 		}
		$r = [
		'opt_uuid'=>$id,
		'trade_type'=>6,
		'main_body_uuid'=>$repayInfo['repay_main_body_uuid']
		];
		EodTradeDb::dataCreate($r);
	}
	
	public function details($params , $cols , $page , $pageSize)
	{
		$cols = $cols?$cols:'*';
		$keys = ['f.transfer_status','f.order_create_people'
				,'f.loan_main_body_uuid','f.collect_main_body_uuid','f.loan_type','f.loan_status','repay_transfer_status','repay_status','f.is_pay_off','o.order_num'];
		$where = [];
		foreach ($params as $key => $val) {
			if (in_array($key, $keys)) {
				$where[] = [$key, '=', $val];
			}
		}
		if(!empty($params['repay_main_body_uuids'])&&is_array($params['repay_main_body_uuids'])&&count($params['repay_main_body_uuids'])>0){
			$where[] = ['repay_main_body_uuid','in',$params['repay_main_body_uuids']];
		}
		
		if(!empty($params['apply_begin_time'])){
			$where[] = ['r.create_time', '>=', $params['apply_begin_time']];
		}
		if(!empty($params['apply_end_time'])){
			$where[] = ['r.create_time', '<=', $params['apply_end_time']];
		}
		if(!empty($params['approve_begin_time'])){
			$where[] = ['r.update_time', '>=', $params['approve_begin_time']];
		}
		if(!empty($params['approve_end_time'])){
			$where[] = ['r.update_time', '<=', $params['approve_end_time']];
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
			$result['data'] = $this->table('m_repay r')->join( " m_loan_transfer f "," f.uuid = r.loan_transfer_uuid ")->join(" m_loan_order o "," o.uuid = f.loan_order_uuid ")->field($cols)
			->where($where)->order(['r.create_time' => 'desc'])->select()->toArray();
		} else {
			$count = $this->table('m_repay r')->join( " m_loan_transfer f "," f.uuid = r.loan_transfer_uuid ")->join(" m_loan_order o "," o.uuid = f.loan_order_uuid ")->where($where)->count();
			if(!empty($count)){
				$result['count'] = $count;
				$result['data'] = $this->table('m_repay r')->join(" m_loan_transfer f "," f.uuid = loan_transfer_uuid ")->join(" m_loan_order o "," o.uuid = f.loan_order_uuid ")->field($cols)->where($where)->order(['r.create_time' => 'desc'])->page($page, $pageSize)->select()->toArray();
			}
		}
		return $result;
	}
}
