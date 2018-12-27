<?php
/**
 * 内部调拨详情
 * @author sun
 * @since 2018-04-25
 */
use money\service\BaseService;
use money\model\BankAccount;
use money\model\InnerTransfer;
use money\model\MainBody;
use money\model\SysFile;

class InnerTransferDetail extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'uuid' => 'require',
    ];

	public function exec()
	{
		$cols = "*";
		$obj = InnerTransfer::getDataById($this->m_request['uuid'] , $cols);
		if(!$obj||!$obj['uuid'])
		{
			throw new Exception("查询结果为空" , ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		
		//权限验证
		MainBody::validateAuth($this->m_request['sessionToken'], $obj['main_body_uuid']);
		
		$obj = SysFile::changeUuidsToPath($obj , 'annex_uuids' , 'annex');
// 		$obj = MapUtil::getMapdArrayByParams($obj , 'transfer_status' , 'transfer_status');
// 		$obj = MapUtil::getMapdArrayByParams($obj , 'pay_status' , 'pay_status');
		$data = array();
		$data[] = $obj;
	
		$data = MainBody::changeUuidToName($data , 'main_body_uuid' , 'main_body_name');
		$data = $data[0];
		
		if(isset($data['pay_account_uuid'])){
			$bank = new BankAccount();
			$pay_info = $bank->getDataById($data['pay_account_uuid']);
			$data['pay_bank_name'] = $pay_info['bank_name'];
		}
		
		
		//审批流详情
// 		$flow_code = $data['real_pay_type']==1?'inner_transfer_pay_type_1_code':'inner_transfer_pay_type_2_code';
		$params = array(
				"sessionToken"=>$this->m_request['sessionToken'],
				'flow_code'=>'inner_transfer_pay_type_1_code,inner_transfer_pay_type_2_code',
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