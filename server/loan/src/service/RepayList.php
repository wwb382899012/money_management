<?php
use money\service\BaseService;
use money\model\MainBody;
use money\model\LoanTransfer;
use money\model\Repay;

class RepayList extends BaseService {
	protected $rule = [ 
			'sessionToken' => 'require' 
	];
	public function exec() {
		$params = $this->m_request;
		$queryArray = array (
				'f.transfer_create_people' => $this->getDataByArray ( $params, 'transfer_create_people' ),
				'f.loan_status' => LoanTransfer::LOAN_STATUS_PAID,
				'apply_begin_time' => $this->getDataByArray ( $params, 'apply_begin_time' ),
				'apply_end_time' => $this->getDataByArray ( $params, 'apply_end_time' ),
				'approve_begin_time' => $this->getDataByArray ( $params, 'approve_begin_time' ),
				'approve_end_time' => $this->getDataByArray ( $params, 'approve_end_time' ),
                'loan_begin_datetime'=>$this->getDataByArray($params, 'loan_begin_datetime'),
                'loan_end_datetime'=>$this->getDataByArray($params, 'loan_end_datetime'),
                'f.is_pay_off'=>$this->getDataByArray($params, 'is_pay_off'),
				'f.loan_main_body_uuid' => $this->getDataByArray ( $params, 'loan_main_body_uuid' ),
				'f.collect_main_body_uuid' => $this->getDataByArray ( $params, 'collect_main_body_uuid' ) ,
				'repay_transfer_status'=>$this->getDataByArray ( $params, 'repay_transfer_status' ) ,
				'o.order_num'=>$this->getDataByArray ( $params, 'loan_order_num' ),
				'repay_status'=>$this->getDataByArray ( $params, 'repay_status')
		);
		// 过滤掉NULL值
		$queryArray = array_filter ( $queryArray, function ($v) {
			return $v !== null;
		} );
		
		$main_body_ids = MainBody::getMainBodys ( $this->m_request ['sessionToken'] );
		if (count ( $main_body_ids ) == 0) {
			$result = [ 
					'page' => $this->getDataByArray ( $params, 'page' ),
					'limit' => $this->getDataByArray ( $params, 'limit' ),
					'count' => 0,
					'data' => [ ] 
			];
			$this->packRet ( ErrMsg::RET_CODE_SUCCESS, $result );
			return;
		}
		$queryArray ['repay_main_body_uuids'] = $main_body_ids;
		
		$obj = new Repay();
		$ret = $obj->details ( $queryArray, 'f.* , r.id,o.order_num,r.repay_status,r.repay_transfer_status,r.repay_transfer_num,r.need_repay_ticket_back,edit_status,r.forecast_date' 
				, $this->getDataByArray ( $params, 'page' ), $this->getDataByArray ( $params, 'limit' ));
		
		$list = $ret ['data'];
		if (is_array ( $list ) && count ( $list ) > 0) {
			
			// $list = MapUtil::getMapdArrayByParams($list , 'transfer_status' , 'transfer_status');
			// $list = MapUtil::getMapdArrayByParams($list , 'loan_status' , 'loan_status');
			
			$list = MainBody::changeUuidToName ( $list, 'loan_main_body_uuid', 'loan_main_body' );
			$list = MainBody::changeUuidToName ( $list, 'collect_main_body_uuid', 'collect_main_body' );
			// //获取当前节点权限
			
			$ids = array_column($list, 'uuid');
			$map = array();
			$flow_code = 'repay_transfer_pay_type_1_code,repay_transfer_pay_type_2_code';
			$res = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList',
			['flow_code'=>$flow_code,'instance_id'=>implode(',' , $ids ),'sessionToken'=>$this->m_request['sessionToken']]);
			if(isset($res['code'])&&$res['code']==0&&isset($res['data'])){
				$flowInfos = $res['data'];
				foreach($flowInfos as $flowInfo){
					$map[$flowInfo['instance_id']] = $flowInfo;
				}
			}
			
			// $map_yq = array();
			// $flow_code = 'repay_transfer_pay_type_2_code';
			// $res = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList',
			// ['flow_code'=>$flow_code,'instance_id'=>implode(',' , $ids ),'sessionToken'=>$this->m_request['sessionToken']]);
			// if(isset($res['code'])&&$res['code']==0&&isset($res['data'])){
			// $flowInfos = $res['data'];
			// foreach($flowInfos as $flowInfo){
			// $map_yq[$flowInfo['instance_id']] = $flowInfo;
			// }
			// }
			foreach($list as &$tran){
			//uuid列表
				if(isset($map[$tran['uuid']])){
					$tran['cur_node_auth'] = $map[$tran['uuid']]['cur_node_auth'];
				}
			}
		}
		$ret['data'] = $list;
		$this->packRet ( ErrMsg::RET_CODE_SUCCESS, $ret );
	}
}

?>