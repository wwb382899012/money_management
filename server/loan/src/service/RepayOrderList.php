<?php

use money\service\BaseService;
use money\model\MainBody;
use money\model\RepayOrder;

class RepayOrderList extends BaseService{
	protected $rule = [
		'sessionToken' => 'require'
	];
	
	public function exec()
	{
		$params = $this->m_request;
		$queryArray = array(
				'm.order_create_people'=>$this->getDataByArray($params, 'order_create_people'),
				'repay_order_status'=>$this->getDataByArray($params, 'repay_order_status'),
				'apply_begin_time'=>$this->getDataByArray($params, 'apply_begin_time'),
				'apply_end_time'=>$this->getDataByArray($params, 'apply_end_time'),
				'approve_begin_time'=>$this->getDataByArray($params, 'approve_begin_time'),
				'approve_end_time'=>$this->getDataByArray($params, 'approve_end_time'),
				'm.repay_main_body_uuid'=>$this->getDataByArray($params, 'repay_main_body_uuid'),
				'm.out_order_num'=>$this->getDataByArray($params, 'out_order_num'),
				'o.order_num'=>$this->getDataByArray($params, 'loan_order_num'),
				'f.loan_main_body_uuid'=>$this->getDataByArray($params, 'collect_main_body_uuid')
		);
	
		$main_body_ids = MainBody::getMainBodys($this->m_request['sessionToken']);
		if(count($main_body_ids)==0){
			$result = ['page'=>$this->getDataByArray($params, 'page'), 'limit'=>$this->getDataByArray($params, 'limit'), 'count'=>0, 'data'=>[]];
			$this->packRet(ErrMsg::RET_CODE_SUCCESS, $result);
			return;
		}
	
		$queryArray['repay_main_body_uuids'] = $main_body_ids;
	
		//过滤掉NULL值
		$queryArray = array_filter($queryArray, function ($v) {
			return $v !== null;
		});
		$obj = new RepayOrder();
		$ret = $obj->orderDetails($queryArray,'m.*,f.amount loan_amount,f.currency loan_currency, '
				.'f.loan_main_body_uuid,f.real_pay_date,f.forecast_datetime,f.rate,f.plus_require'
				,$this->getDataByArray($params, 'page')
				,$this->getDataByArray($params, 'limit'));
	
		$list = $ret['data'];
		if(is_array($list)&&count($list)>0){
			//转化字典，主体
			$ids = array_column($list, 'uuid');
			//             $list = MapUtil::getMapdArrayByParams($list , 'order_status' , 'order_status');
	
			$list = MainBody::changeUuidToName($list , 'repay_main_body_uuid' , 'repay_main_body');
			$list = MainBody::changeUuidToName($list , 'collect_main_body_uuid' , 'collect_main_body');
			//获取当前节点权限
			$res = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList',
					['flow_code'=>'repay_order','instance_id'=>implode(',' , $ids ),'sessionToken'=>$this->m_request['sessionToken']]);
			if($res['code'] == 0 && !empty($res['data'])){
				$flowInfos = $res['data'];
				$map = array();
				foreach($flowInfos as $flowInfo){
					$map[$flowInfo['instance_id']] = $flowInfo;
				}
			}
				
			foreach($list as &$order){
				//uuid列表
				if(isset($map[$order['uuid']])){
					$order['cur_node_auth'] = $map[$order['uuid']]['cur_node_auth'];
				}
			}
		}
		$ret['data'] = $list;
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
}

?>