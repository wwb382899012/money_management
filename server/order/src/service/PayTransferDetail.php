<?php
/**
*	付款调拨详情
*	@author sun
*	@since 2018-03-11
*/
use money\service\BaseService;
use money\base\MapUtil;
use money\model\MainBody;
use money\model\BankAccount;
use money\model\PayTransfer;

class PayTransferDetail extends BaseService
{
    protected $rule = [
        //'sessionToken' => 'require',
        'uuid' => 'require',
    ];

	public function exec()
	{
		$cols = "*";
		$data = PayTransfer::getDataById($this->m_request['uuid'] , $cols);
		if(!$data||!$data['uuid'])
    	{
    		throw new Exception("查询结果为空" , ErrMsg::RET_CODE_DATA_NOT_EXISTS);
    	}
    	//权限验证
    	MainBody::validateAuth($this->m_request['sessionToken'], $data['pay_main_body_uuid']);

		$array = array();
		$array[] = $data;
		$array = MainBody::changeUuidToName($array , 'pay_main_body_uuid' , 'pay_main_body');
// 		$array = MainBody::changeUuidToName($array , 'collect_main_body_uuid' , 'collect_main_body');
		$array = MapUtil::getMapdArrayByParams($array , 'transfer_pay_type' , 'pay_type');
		$data = $array[0];
		
		if(isset($data['pay_account_uuid'])&&!empty($data['pay_account_uuid'])){
			$bank = new BankAccount();
			$pay_info = $bank->getDataById($data['pay_account_uuid']);
			$data['pay_bank_name'] = $pay_info['bank_name'];
		}
		
		//审批流详情
		$flow_code ='pay_transfer_pay_type_1_code,pay_transfer_pay_type_2_code';
		$params = array(
				"sessionToken"=>$this->m_request['sessionToken'],
				'flow_code'=>$flow_code,
				'instance_id'=>$data['uuid']
		);
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.flow.DetailList" , $params);
		if(!$ret||$ret['code']!=0||count($ret['data'])==0||!isset($ret['data'][0]['instance_id'])){
		}else{
			$flow_detail = $ret['data'][0];
			$data['node_list'] = $flow_detail['node_list'];
			$data['approve_status'] = $flow_detail['status'];
			$data['cur_node_auth'] = $flow_detail['cur_node_auth'];
		}
		
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $data);
	}
}
