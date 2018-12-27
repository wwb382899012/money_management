<?php
use money\service\BaseService;
use money\base\MapUtil;
use money\model\MainBody;
use money\model\BankAccount;
use money\model\LoanCashFlow;
use money\model\LoanTransfer;
use money\model\LoanOrder;
use money\model\RepayOrder;
use money\model\Repay;

class RepayDetail extends BaseService{
	protected $rule = [
	'sessionToken' => 'require',
	'uuid' => 'require',
	];
	
	public function exec()
	{
		$cols = "*";
		$repayInfo = Repay::getDataById($this->m_request['uuid'] , $cols);
		if(!$repayInfo||!$repayInfo['id'])
		{
			throw new Exception("查询结果为空" , ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}

// 		$obj = LoanTransfer::getDataById($repayInfo['loan_transfer_uuid'] , $cols);
		$obj = [];
		$obj['loan_transfer_uuid'] = $repayInfo['loan_transfer_uuid'];
		//权限验证
		MainBody::validateAuth($this->m_request['sessionToken'], $repayInfo['repay_main_body_uuid']);
		$obj = $repayInfo;		
		
		$repay_account = BankAccount::getDataById($repayInfo['repay_account_uuid']);
		$obj['repay_bank_name'] = $repay_account['bank_name'];
		$obj['repay_bank_account'] = $repay_account['bank_account'];
		$collect_account = BankAccount::getDataById($repayInfo['collect_account_uuid']);
		$obj['collect_bank_name'] = $collect_account['bank_name'];
		$obj['collect_bank_account'] = $collect_account['bank_account'];
		
		$list[] = $obj;
		//         $list = MapUtil::getMapdArrayByParams($list , 'order_status' , 'order_status');
		//         $list = MapUtil::getMapdArrayByParams($list , 'loan_status' , 'loan_status');
	
		$list = MainBody::changeUuidToName($list , 'repay_main_body_uuid' , 'repay_main_body');
		$list = MainBody::changeUuidToName($list , 'collect_main_body_uuid' , 'collect_main_body');
		$data = $list[0]; 
	
		$loanInfo = LoanTransfer::getDataById($repayInfo['loan_transfer_uuid'] , $cols);
		$orderInfo = LoanOrder::getDataById($loanInfo['loan_order_uuid']);
		$data['out_order_num'] = $orderInfo['out_order_num'];
		$data['order_num'] = $orderInfo['order_num'];
		$data['rate'] = $loanInfo['rate'];
		$data['loan_datetime'] = $loanInfo['loan_datetime'];
        $data['external_detail_url'] = getExternalDetailUrl($orderInfo['system_flag'], $orderInfo['out_order_num']);//获取外部系统详情页面URL
		
		//获取现金流详情
		$c = new LoanCashFlow();
		$cashDatas = $c->field('uuid,repay_date,cash_flow_type,amount,real_amount,info,cash_status,index,real_repay_date')
			->where(array('loan_transfer_uuid'=>$repayInfo['loan_transfer_uuid']))->order(['cash_flow_type' => 'asc'])->select()->toArray();
		
		if(is_array($cashDatas)&&count($cashDatas)>0){
			$cashDetail = array_filter($cashDatas,function($v){
				return MapUtil::getMapdArrayByParams($v , 'cash_flow_type' , 'cash_flow_type');
			});
			$data['cashDetail'] = $cashDetail;
		}
		
		//获取还款指令详情
		$order = new RepayOrder();
		$arrays  = $order->table('m_repay_order')->field('*')->where(['repay_id'=>$this->m_request['uuid']])->order(['create_time' => 'desc'])->select()->toArray();
		if(is_array($arrays)&&count($arrays)>0){
			$data['orderDetail'] = $arrays;
		}
		
// 		//获取还款现金流详情
// 		$c = new RepayCashFlow();
// 		$cashDatas = $c->loadDatas(array('loan_transfer_uuid'=>$repayInfo['loan_transfer_uuid']), '*');
// 		if(is_array($cashDatas)&&count($cashDatas)>0){
// 			$repayCashDetail = array_filter($cashDatas,function($v){
// 				return MapUtil::getMapdArrayByParams($v , 'cash_flow_type' , 'cash_flow_type');
// 			});
// 			$ret = array();
// 			foreach($repayCashDetail as $v){
// 				$raccount = BankAccount::getDataById($v['repay_account_uuid']);
// 				$caccount = BankAccount::getDataById($v['collect_account_uuid']);
// 				$v['repay_bank_account'] = $raccount['bank_account'];
// 				$v['collect_bank_account'] = $caccount['bank_account'];
// 				$v['repay_bank_name'] = $raccount['bank_name'];
// 				$v['collect_bank_name'] = $caccount['bank_name'];
// 				$ret[] = $v;
// 			}
// 			$repayCashDetail = $ret;
// 			$repayCashDetail = MainBody::changeUuidToName($repayCashDetail, 'repay_main_body_uuid', 'repay_main_body');
// 			$repayCashDetail = MainBody::changeUuidToName($repayCashDetail, 'collect_main_body_uuid', 'collect_main_body');
// 			$data['repayCashDetail'] = $repayCashDetail;
// 		}
		
		//审批流详情
		$params = array(
				"sessionToken"=>$this->m_request['sessionToken'],
				'flow_code'=>'repay_apply',
				'instance_id'=>$repayInfo['id']
		);
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.flow.DetailList" , $params);
		if(isset($ret['code'])&&$ret['code']==0&&is_array($ret['data'])&&isset($ret['data'][0]['instance_id'])){
			$flow_detail = $ret['data'][0];
			$data['node_list'] = $flow_detail['node_list'];
			$data['approve_status'] = $flow_detail['status'];
			$data['cur_node_auth'] = $flow_detail['cur_node_auth'];
		}
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $data);
	}
}

?>