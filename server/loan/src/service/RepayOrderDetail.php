<?php
use money\model\LoanTransfer;
use money\model\MainBody;
use money\service\BaseService;
use money\model\RepayOrder;

class RepayOrderDetail extends BaseService{
	protected $rule = [
		'sessionToken' => 'require',
		'id' => 'require',
	];
	
	public function exec()
	{
		$cols = "*";
		$obj = RepayOrder::getDataById($this->m_request['id'] , $cols);

		
		if(!$obj||!isset($obj['id'])){
			throw new Exception("查询结果为空" , ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		//权限验证
		MainBody::validateAuth($this->m_request['sessionToken'], $obj['repay_main_body_uuid']);
        $obj['external_detail_url'] = getExternalDetailUrl($obj['system_flag'], $obj['out_order_num']);//获取外部系统详情页面URL
		
		$loan_info = LoanTransfer::getDataById($obj['loan_transfer_uuid']);
		$obj['loan_amount'] = $loan_info['amount'];
		$obj['loan_currency'] = $loan_info['currency'];		
		$obj['collect_bank_name'] = $loan_info['loan_bank_name'];
		$obj['collect_account_name'] = $loan_info['loan_account_name'];
		$obj['collect_bank_account'] = $loan_info['loan_bank_account'];
		$obj['repay_account_name'] = $loan_info['collect_account_name'];
		$obj['repay_account_uuid'] = $loan_info['collect_account_uuid'];
		$obj['repay_bank_account'] = $loan_info['collect_bank_account'];
		$obj['repay_bank_name'] = $loan_info['collect_bank_name'];
		$obj['rate'] = $loan_info['rate'];
		$obj['bs_background'] = $loan_info['bs_background'];
		$obj['special_require'] = $loan_info['special_require'];
		$obj['contact_annex'] = $loan_info['contact_annex'];
		$obj['special_require'] = $loan_info['special_require'];
		$obj['order_status'] = $loan_info['order_status'];
		$obj['loan_status'] = $loan_info['loan_status'];
		$obj['special_require'] = $loan_info['special_require'];
		$obj['real_pay_date'] = $loan_info['real_pay_date'];
		$obj['forecast_datetime'] = $loan_info['forecast_datetime'];
		$obj['plus_require'] = $loan_info['plus_require'];
		$obj['loan_amount'] = $loan_info['amount'];	
		
		$data = array();
		$data[] = $obj;
	
		$data = MainBody::changeUuidToName($data , 'repay_main_body_uuid' , 'repay_main_body');
		$data = MainBody::changeUuidToName($data , 'collect_main_body_uuid' , 'collect_main_body');
		$data = $data[0];
	
		//审批流详情
		$params = array(
				"sessionToken"=>$this->m_request['sessionToken'],
				'flow_code'=>'repay_order',
				'instance_id'=>$obj['uuid']
		);
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.flow.DetailList" , $params);
		if(!$ret||$ret['code']!=0||count($ret['data'])==0||!isset($ret['data'][0]['instance_id'])){
			// 			throw new \Exception('审批流调用错误' , ErrMsg::RET_CODE_SERVICE_FAIL);
		}else{
			$flow_detail = $ret['data'][0];
			$data['node_list'] = $flow_detail['node_list'];
			$data['approve_status'] = $flow_detail['status'];
			$data['cur_node_auth'] = $flow_detail['cur_node_auth'];
		}
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $data);
	}
}

?>