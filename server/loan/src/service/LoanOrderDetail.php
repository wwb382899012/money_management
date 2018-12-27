<?php
/**
 * 借款订单详情
 * @author sun
 * 2018-04-11
 */
use money\service\BaseService;
use money\model\MainBody;
use money\model\BankAccount;
use money\model\LoanOrder;

class LoanOrderDetail extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'uuid' => 'require',
    ];

	public function exec()
	{
		$cols = "*";
		$obj = LoanOrder::getDataById($this->m_request['uuid'] , $cols);
		if(!$obj||!isset($obj['uuid'])){
			throw new Exception("查询结果为空" , ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		//权限验证
		MainBody::validateAuth($this->m_request['sessionToken'], $obj['loan_main_body_uuid']);
        $obj['external_detail_url'] = getExternalDetailUrl($obj['system_flag'], $obj['out_order_num']);//获取外部系统详情页面URL
		
		$data = array();
		$data[] = $obj;
	
		$data = MainBody::changeUuidToName($data , 'loan_main_body_uuid' , 'loan_main_body');
		$data = MainBody::changeUuidToName($data , 'collect_main_body_uuid' , 'collect_main_body');
        $data = $data[0];
        
        if(isset($data['loan_account_uuid'])&&!empty($data['loan_account_uuid'])){
	        $bank = new BankAccount();
	        $loan_info = $bank->getDataById($data['loan_account_uuid']);
	        $data['loan_bank_name'] = $loan_info['bank_name'];
        }
		
        $flow_code = $data['loan_type']==1?'loan_order':'repay_order';
		//审批流详情
		$params = array(
				"sessionToken"=>$this->m_request['sessionToken'],
				'flow_code'=>$flow_code,
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