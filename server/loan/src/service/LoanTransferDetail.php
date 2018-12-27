<?php
/**
*	借款调拨详情
*	@author sun
*	@since 2018-03-11
*/
use money\service\BaseService;
use money\base\MapUtil;
use money\model\MainBody;
use money\model\BankAccount;
use money\model\LoanTransfer;
use money\model\LoanOrder;
use money\model\LoanCashFlow;

class LoanTransferDetail extends BaseService
{
    protected $rule = [
        'sessionToken' => 'require',
        'uuid' => 'require',
    ];

	public function exec()
	{
		$cols = "*";
		$obj = LoanTransfer::getDataById($this->m_request['uuid'] , $cols);
		if(!$obj||!$obj['uuid'])
    	{
    		throw new Exception("查询结果为空" , ErrMsg::RET_CODE_DATA_NOT_EXISTS);
    	}
    	//权限验证
//    	MainBody::validateAuth($this->m_request['sessionToken'], $obj['loan_main_body_uuid']);
        $list[] = $obj;
//         $list = MapUtil::getMapdArrayByParams($list , 'order_status' , 'order_status');
//         $list = MapUtil::getMapdArrayByParams($list , 'loan_status' , 'loan_status');

        $list = MainBody::changeUuidToName($list , 'loan_main_body_uuid' , 'loan_main_body');
        $list = MainBody::changeUuidToName($list , 'collect_main_body_uuid' , 'collect_main_body');
        $data = $list[0];

        if(isset($data['loan_account_uuid'])&&!empty($data['loan_account_uuid'])){
        	$bank = new BankAccount();
        	$loan_info = $bank->getDataById($data['loan_account_uuid']);
        	$data['loan_bank_name'] = $loan_info['bank_name'];
        }
        
    	$orderInfo = LoanOrder::getDataById($obj['loan_order_uuid']);
    	$data['out_order_num'] = $orderInfo['out_order_num'];
        $data['external_detail_url'] = getExternalDetailUrl($orderInfo['system_flag'], $orderInfo['out_order_num']);//获取外部系统详情页面URL
        
		//获取现金流详情
		$c = new LoanCashFlow();
		$cashDatas = $c->field( 'index,uuid,repay_date,real_repay_date,cash_flow_type,amount,real_amount,info')->where(array('loan_transfer_uuid'=>$this->m_request['uuid']))->order(['cash_flow_type' => 'asc'])->select()->toArray();

		if(is_array($cashDatas)&&count($cashDatas)>0){
			$cashDetail = array_filter($cashDatas,function($v){
				return MapUtil::getMapdArrayByParams($v , 'cash_flow_type' , 'cash_flow_type');
			});
			$data['cashDetail'] = $cashDetail;
		}
		
		$flow_code = 'loan_transfer_pay_type_1_code,loan_transfer_pay_type_2_code';
		//审批流详情
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
