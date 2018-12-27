<?php
use money\model\InterfacePriv;
use money\model\MainBody;
use money\model\BankAccount;
use money\service\OrderBaseService;
use money\model\EodTradeDb;
use money\model\LoanOrder as LoanOrderModel;

class LoanOrder extends OrderBaseService{

    protected $rule = [
        //'sessionToken' => 'require',
        'system_flag' => 'require',
        'out_order_num' => 'require',
        'loan_main_body' => 'require',
        //'pay_bank_name' => 'require',
        //'pay_bank_account' => 'require',
        'collect_main_body' => 'require',
        'collect_account_name' => 'require',
        'collect_bank_account' => 'require',
        //'collect_bank_name' => 'require',
        //'collect_bank_account' => 'require',
        'amount' => 'require|integer',
        'loan_date' => 'require|date',
        'forecast_date' => 'require|date',
        'rate' => 'require|number',
        'order_create_people'=>'require',
    ];

	public function exec()
	{
		$o_params = $this->m_request;
		
		/**
		 *	1、付款收款主体验证
		*	2、付款主体账户如果存在，验证是否和系统账户信息一致、是否在当前主体下，是否有权限对这个账户做操作
		*	       收款主体为必填，验证是否和系统账户信息一致、是否在当前主体下 (收款主体不需要判断调用系统是否有权限对这个账户操作)
		*	3、是否调用系统与权限对付款主体账户操作
		*	4、判断付款账户余额
		*	5、数据加锁
		*	6、保存指令数据，收款账户银行编码转换
		*	7、发起审批，如果审批发起失败，则系统报错回滚
		*	8、数据解锁
		*/
		//step 1
		$loan_main_body = MainBody::getByName($o_params['loan_main_body']);
		if(empty($loan_main_body)){
			throw new Exception('借款主体不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		if($loan_main_body['is_internal']==2){
			throw new Exception('借款主体必须为内部主体',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}
		$collect_main_body = MainBody::getByName($o_params['collect_main_body']);
		if(empty($collect_main_body)){
			throw new Exception('收款主体不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		if($collect_main_body['is_internal']==2){
			throw new Exception('收款主体必须为内部主体',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}
		//step 2 、3
		$oBankAmount = new BankAccount();
		if(isset($o_params['loan_bank_account'])){
			
			$loan_bank_account = $oBankAmount->getByAccount($o_params['loan_bank_account']);
			if(empty($loan_bank_account)){
				throw new Exception('借款账户不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
			}
			if(!isset($loan_bank_account['main_body_uuid'])||$loan_bank_account['main_body_uuid']!=$loan_main_body['uuid']){
				throw new Exception('借款账户不在付款主体下',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
			}
		
			if(!isset($this->m_request['loan_account_name'])||$loan_bank_account['account_name']!=$this->m_request['loan_account_name']){
				throw new Exception('借款账户户名错误',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
			}
			$system_flags = explode(',' , $loan_bank_account['interface_priv']);
			$v = new InterfacePriv();
			$priv = $v->loadDatas(['system_flag'=>$o_params['system_flag']]);
			if(!in_array($priv[0]['uuid'],$system_flags)){
				throw new Exception('该系统无权限对这个账户进行操作',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
			}
		}
		$collect_bank_account = $oBankAmount->getByAccount($o_params['collect_bank_account']);
		if(empty($collect_bank_account)){
			throw new Exception('收款账户不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		if(empty($collect_bank_account['main_body_uuid'])||$collect_bank_account['main_body_uuid']!=$collect_main_body['uuid']){
			throw new Exception('收款账户不在付款主体下',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}
		if(!isset($o_params['collect_account_name'])||$collect_bank_account['account_name']!=$o_params['collect_account_name']){
			throw new Exception('收款账户户名错误',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}
		
// 		if($this->m_request['loan_type']!='1'){
// 			if(!isset($this->m_request['loan_out_order_num'])){
// 				throw new Exception('还款外部编号不存在',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
// 			}
// 			$obj = new LoanOrderModel();
// 			$loan_order = $obj->loadDatas(['out_order_num'=>$this->m_request['loan_out_order_num']]);
// 			if(!is_array($loan_order)||count($loan_order)==0){
// 				throw new Exception('还款外部编号不存在',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
// 			}
// 			$order_num = $loan_order[0]['order_num'];
// 		}
		
		//step 4
		
		$obj = new LoanOrderModel();
		$params['system_flag'] = $o_params['system_flag'];
		$params['out_order_num'] = $o_params['out_order_num'];
		$obj->params = $params;
		if(! $obj->getLock()){
			throw new Exception("当前下单指令编号重复",ErrMsg::RET_CODE_OUT_ORDER_NUM_DULICATE);
		}

		try{
            $obj->startTrans();
		
			//step 5
			$parmas = array();
			$params['order_num'] = LoanOrderModel::getOrderNum($loan_main_body['short_code']);
		
			$params['loan_main_body_uuid'] = $loan_main_body['uuid'];
			if(isset($o_params['loan_bank_account'])){
				if(!isset($o_params['loan_account_name'])){
					throw new Exception();
				}
				$params['loan_bank_account'] = $o_params['loan_bank_account'];
				$params['loan_account_uuid'] = $loan_bank_account['uuid'];
				$params['loan_account_name'] = $o_params['loan_account_name'];
				$params['loan_bank_name'] = $loan_bank_account['bank_name'];
			}
		
			$params['collect_main_body'] = $o_params['collect_main_body'];
            $params['collect_main_body_uuid'] = $collect_main_body['uuid'];
            $params['collect_account_name'] = $o_params['collect_account_name'];
            $params['collect_bank_account'] = $o_params['collect_bank_account'];
            $params['collect_bank_name'] = $collect_bank_account['bank_name'];
            $params['collect_account_uuid'] = $collect_bank_account['uuid'];
			
			$params['amount'] = $o_params['amount'];
			$currency = $this->getDataByArray($o_params,'currency');
			$params['currency'] = isset($currency)?$currency:'cny';
			//$params['financing_dict_key'] = $this->getDataByArray($o_params,'financing_dict_key') ;
			$params['bs_background'] = $this->getDataByArray($o_params,'bs_background');
			$params['loan_datetime'] = $this->getDataByArray($o_params,'loan_date');
			$params['forecast_datetime'] = $this->getDataByArray($o_params,'forecast_date');
			$params['order_create_people'] = $this->getDataByArray($o_params,'order_create_people');
			$params['plus_require'] = $this->getDataByArray($o_params,'plus_require');
			$params['rate'] = $this->getDataByArray($o_params,'rate') ;
			
			$params['contact_annex'] = $this->getDataByArray($o_params,'contact_annex');
			$params['order_status'] = LoanOrderModel::ORDER_STATUS_WAITING;
			$params['loan_status'] = LoanOrderModel::LOAN_STATUS_UNPAID;
			
			$obj = new LoanOrderModel();
			$obj->params = $params;
			$uuid = $obj->saveOrUpdate();

			$obj->unlock();
            $obj->commit();
			
		}catch(Exception $e){
            $obj->rollback();
			$obj->unlock();
// 			throw new Exception("借款指令下单失败".$e->getMessage(),ErrMsg::RET_CODE_LOAN_ORDER_ERROR);
			throw $e;
		}
		//审批流发起
		//审批流发起失败，则更新数据状态，
		$f = array(
				"flow_code"=>'loan_order',
				"instance_id"=>$uuid,
				"main_body_uuid"=>$loan_main_body['uuid'],
				"info"=>''
		);
			
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.flow.Start" , $f);
		if(!is_array($ret)||!isset($ret['code'])||$ret['code']!=0){
			$obj->params = [
				'uuid'=>$uuid,
				'order_status'=>LoanOrderModel::ORDER_STATUS_REFUSE,
				'loan_status'=>LoanOrderModel::LOAN_STATUS_FAIL
			];
			$obj->saveOrUpdate();
// 			throw new Exception('审批流发起错误',ErrMsg::RET_CODE_SERVICE_FAIL);
		}else{
			$r = [
				'out_order_num'=>$this->m_request['out_order_num'],
				'main_body_uuid'=>$loan_main_body['uuid'],
				'order_create_time'=>date('Y-m-d H:i:s'),
				'limit_date'=>$this->m_request['loan_date'],
				'opt_uuid'=>$uuid,
				'trade_type'=>3
			];
			EodTradeDb::dataCreate($r);
		}
		$ret = [
			'out_order_num'=>$params['out_order_num'],
			'order_num'=>$params['order_num'],
			'amount'=>$params['amount']
		];
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
}