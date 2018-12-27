<?php
use money\service\OrderBaseService;
use money\model\LoanOrder;
use money\model\LoanTransfer;
use money\model\MainBody;
use money\model\EodTradeDb;
use money\model\RepayOrder as RepayOrderModel;
use money\model\Repay;

class RepayOrder extends OrderBaseService{
	protected $rule = [
		//'sessionToken' => 'require',
		'system_flag' => 'require',
		'out_order_num' => 'require',
		'loan_out_order_num'=>'require',
// 		'loan_main_body' => 'require',
		//'pay_bank_name' => 'require',
		//'pay_bank_account' => 'require',
// 		'collect_main_body' => 'require',
// 		'collect_account_name' => 'require',
// 		'collect_bank_account' => 'require',
		//'collect_bank_name' => 'require',
		//'collect_bank_account' => 'require',
		'amount' => 'require|integer',
// 		'loan_datetime' => 'require|date',
		'require_repay_date' => 'require|date',
// 		'rate' => 'require|number',
		'order_create_people'=>'require',
		'repay_type'=>'require'
	];
	
	public function exec()
	{
		/**
		 * 1、借款指令是否存在
		 * 2、借款还款是否同一系统调用
		 * 3、数据加锁
		 * 4、插入还款指令
		 * 5、更新借款还款指令到达日期
		 * 6、调用审批流发起接口
		 * 7、数据解锁
		 */
		//step 1
		$loan_out_order_num = $this->m_request['loan_out_order_num'];
		$loan = new LoanOrder();
		$loan_info = $loan->loadDatas(['out_order_num'=>$this->m_request['loan_out_order_num']]);
		if(!is_array($loan_info)||count($loan_info)==0){
			throw new Exception('借款外部系统指令编号不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		
		$tran = new LoanTransfer();
		$tranInfo = $tran->loadDatas(['loan_order_uuid'=>$loan_info[0]['uuid']]);
		
		if($loan_info[0]['order_status']!=LoanOrder::ORDER_STATUS_ARCHIVE){
			throw new Exception('借款流程未结束无法还款',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}

		//step 2
		if($this->m_request['system_flag']!=$loan_info[0]['system_flag']){
			throw new Exception('还款调用系统和借款不一致',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}	
		
		//step 3
		$lock = RepayOrderModel::getLock($this->m_request['system_flag'] , $this->m_request['out_order_num']);
		if(is_array($lock)&&isset($lock['id'])){
			$ret = [
				'out_order_num'=>$lock['out_order_num'],
				'order_num'=>$lock['order_num'],
				'amount'=>$lock['amount']
			];
			$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
			return;
		}
		
		//step 4
		$mainBody = MainBody::getDataById($loan_info[0]['collect_main_body_uuid']);
		//获取当前未处理还款数据uuid
		$repay = new Repay();
		$c = $repay->loadDatas(['loan_transfer_uuid'=>$tranInfo[0]['uuid'],'repay_transfer_status'=>Repay::CODE_REPAY_TRANSFER_STATUS_WAITING]);
		if(!isset($c[0]['id'])){
			throw new Exception('还款数据数据不存在',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}

		
		$repayOrder = new RepayOrderModel();
		$repayOrder->startTrans();
		try{
			$params = [
				'system_flag'=>$this->m_request['system_flag'],
				'order_num'=>$repay->getOrderNum($mainBody['short_code']),
				'out_order_num'=>$this->m_request['out_order_num'],
				'loan_out_order_num'=>$this->m_request['loan_out_order_num'],
				'amount'=>$this->m_request['amount'],
				'repay_type'=>$this->m_request['repay_type'],
				'order_create_people'=>$this->m_request['order_create_people'],
				'repay_main_body_uuid'=>$loan_info[0]['collect_main_body_uuid'],
				'collect_main_body_uuid'=>$loan_info[0]['loan_main_body_uuid'],
				'loan_transfer_uuid'=>$tranInfo[0]['uuid'],
				'loan_order_num'=>$loan_info[0]['order_num'],
				'repay_desc'=>$this->getDataByArray($this->m_request,'repay_desc'),
				'require_repay_date'=>$this->m_request['require_repay_date'],
				'repay_annex'=>$this->getDataByArray($this->m_request,'repay_annex'),
				'repay_id'=>$c[0]['id'],
				'update_time'=>date('Y-m-d H:i:s')
			];
			$repayOrder->params = $params;
			$uuid = $repayOrder->saveOrUpdate();
			
			$repay->params = [
				'id'=>$c[0]['id'],
				'edit_status'=>Repay::CODE_EDIT_ORDER_APPROVEING
			];
			$repay->saveOrUpdate();
			
			//step 5
			$tran->params = [
				'uuid'=>$tranInfo[0]['uuid'],
				'repay_order_time'=>date('Y-m-d')
			];
			$tran->saveOrUpdate();
			
			$r = [
				'out_order_num'=>$this->m_request['out_order_num'],
				'main_body_uuid'=>$mainBody['uuid'],
				'order_create_time'=>date('Y-m-d H:i:s'),
				'limit_date'=>$this->m_request['loan_date'],
				'opt_uuid'=>$uuid,
				'trade_type'=>5
			];
			EodTradeDb::dataCreate($r);
			
			$e = new EodTradeDb();
			$repayEod = $e->loadDatas(['opt_uuid'=>$c[0]['id'],'trade_type'=>6]);
			if(is_array($repayEod)&&count($repayEod)>0){
				$e->params = [
					'id'=>$repayEod[0]['id'],
					'order_create_time'=>date('Y-m-d H:i:s')
				];
				$e->saveOrUpdate();
			}
			
			RepayOrderModel::unlock($this->m_request['system_flag'] , $this->m_request['out_order_num']);
			$repayOrder->commit();
		}catch(Exception $e){
			$repayOrder->rollback();
			RepayOrderModel::unlock($this->m_request['system_flag'] , $this->m_request['out_order_num']);
			throw new Exception('指令下单失败|'.$e->getMessage() , $e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
		}
		
		//审批流发起
		$f = array(
				"flow_code"=>'repay_order',
				"instance_id"=>$uuid,
				"main_body_uuid"=>$loan_info[0]['collect_main_body_uuid']
		);
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.flow.Start" , $f);
		if(!is_array($ret)||!isset($ret['code'])||$ret['code']!=0){
			$repay->params = [
				'id'=>$uuid,
				'repay_order_status'=>RepayOrderModel::REPAY_STATUS_REJECT
			];
			$repay->saveOrUpdate();
		}else{
// 			$r = [
// 			'out_order_num'=>$this->m_request['out_order_num'],
// 			'main_body_uuid'=>$mainBody['uuid'],
// 			'order_create_time'=>date('Y-m-d H:i:s'),
// 			'limit_date'=>$this->m_request['forecast_date'],
// 			'opt_uuid'=>$uuid,
// 			'trade_type'=>5
// 			];
// 			EodTradeDb::dataCreate($r);
		}
		
		$ret = [
			'loan_out_order_num'=>$params['loan_out_order_num'],
			'order_num'=>$params['order_num'],
			'amount'=>$params['amount']
		];
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
		
	}
}

?>