<?php

use money\service\BaseService;
use money\model\BankAccount;
use money\model\EodTradeDb;
use money\model\InnerTransfer;

class UpdateTransfer  extends BaseService{
	
	protected $rule = [
		'sessionToken' => 'require',
		'uuid'=>'require',
        'main_body_uuid' => 'require',
        'pay_account_uuid' => 'require',
        'collect_account_uuid' => 'require',
        'real_pay_type' => 'require|integer',
        'amount' => 'require|number',
        'hope_deal_date' => 'require|date',
        'opt_type'=>'require'      //处理类型，1为保存 2为提交
	];
	
	public function exec()
	{
		$params = $this->m_request;
		$queryArray = array(
				'main_body_uuid'=>$this->getDataByArray($params, 'main_body_uuid'),
				'pay_account_uuid'=>$this->getDataByArray($params, 'pay_account_uuid'),
				'collect_account_uuid'=>$this->getDataByArray($params, 'collect_account_uuid'),
				'real_pay_type'=>$this->getDataByArray($params, 'real_pay_type'),
				'pay_remark' => $this->getDataByArray($params, 'pay_remark'),
				'amount'=>$this->getDataByArray($params, 'amount'),
				'hope_deal_date'=>$this->getDataByArray($params, 'hope_deal_date'),
				'currency'=>$this->getDataByArray($params, 'currency'),
				'special_require'=>$this->getDataByArray($params, 'special_require'),
				'annex_uuids'=>$this->getDataByArray($params, 'annex_uuids')
		);
		if($this->m_request['amount']<=0){
			throw new Exception('打款金额必须大于0');
		}
		
		$bank = new BankAccount();
		$pay_info = $bank->getDataById($params['pay_account_uuid']);
		$queryArray['pay_account_name'] = $pay_info['account_name'];
		$queryArray['pay_bank_account'] = $pay_info['bank_account'];
		$collect_info = $bank->getDataById($params['collect_account_uuid']);
		$queryArray['collect_account_name'] = $collect_info['account_name'];
		$queryArray['collect_bank_account'] = $collect_info['bank_account'];
		$queryArray['collect_bank_name'] = $collect_info['bank_name'];
		$queryArray['collect_bank'] = $collect_info['bank_dict_key'];
		$queryArray['collect_city_name'] = $collect_info['city_name'];
		$queryArray['transfer_status'] = $params['opt_type']==2?2:0;
		$queryArray['uuid'] = $params['uuid'];
		
		if($params['real_pay_type']==2&&in_array($pay_info['bank_dict_key'],[4,5])){
			//平安、农行收款账号行号不能为空
			if(empty($collect_info['bank_link_code'])){
				throw new Exception ( '付款银行平安或农行，收款账号行号不能为空，请维护账号后再提交', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR );
			}
		}
		
		$obj = new InnerTransfer();
		$obj->params = $queryArray;
		try{
            $obj->startTrans();
			$uuid = $obj->saveOrUpdate();
				
			//审批流发起
			if($params['opt_type']==2){
				$info = $obj->getDataById($params['uuid']);
				
				$r = [
					'transfer_num'=>$info['order_num'],
					'main_body_uuid'=>$queryArray['main_body_uuid'],
					'transfer_create_time'=>date('Y-m-d H:i:s'),
					'limit_date'=>$queryArray['hope_deal_date'],
					'opt_uuid'=>$uuid,
					'trade_type'=>7
				];
				
				EodTradeDb::dataCreate($r);
				

				$flow_code = $params['real_pay_type']=='1'?'inner_transfer_pay_type_1_code':'inner_transfer_pay_type_2_code';
				$params = array(
						"flow_code"=>$flow_code,
						"instance_id"=>$uuid,
						'main_body_uuid'=>$this->getDataByArray($params, 'main_body_uuid'),
						"sessionToken"=>$this->m_request['sessionToken']
				);
					
				$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.flow.Start" , $params);
				if(!isset($ret['code'])||$ret['code']!=0){
					throw new Exception('审批流调用失败！',$ret['code']);
				}
			}
            $obj->commit();
		}catch(Exception $e){
            $obj->rollback();
			throw new Exception($e->getMessage(),$e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
		}
		
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, array('uuid'=>$uuid));
	}
}

?>