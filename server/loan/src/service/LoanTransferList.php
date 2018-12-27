<?php
/**
*	借款调拨列表
*	@author sun
*	@since 2018-03-11
*/
use money\service\BaseService;
use money\model\MainBody;
use money\model\LoanTransfer;
class LoanTransferList extends BaseService
{
    protected $rule = [
        'sessionToken' => 'require',
        'loan_type' => 'require',
    ];
	
	public function exec()
	{
		$params = $this->m_request;
		$queryArray = array(
            'transfer_create_people'=>$this->getDataByArray($params, 'transfer_create_people'),
            'transfer_status'=>$this->getDataByArray($params, 'transfer_status'),
			'f.loan_status'=>$this->getDataByArray($params, 'loan_status'),
            'apply_begin_time'=>$this->getDataByArray($params, 'apply_begin_time'),
            'apply_end_time'=>$this->getDataByArray($params, 'apply_end_time'),
            'approve_begin_time'=>$this->getDataByArray($params, 'approve_begin_time'),
            'approve_end_time'=>$this->getDataByArray($params, 'approve_end_time'),
            'loan_begin_datetime'=>$this->getDataByArray($params, 'loan_begin_datetime'),
            'loan_end_datetime'=>$this->getDataByArray($params, 'loan_end_datetime'),
            'f.loan_main_body_uuid'=>$this->getDataByArray($params, 'loan_main_body_uuid'),
            'f.collect_main_body_uuid'=>$this->getDataByArray($params, 'collect_main_body_uuid'),
			'order_num'=>$this->getDataByArray($params, 'loan_order_num'),	
			'o.loan_type'=>$this->getDataByArray($params, 'loan_type')
        );
        //过滤掉NULL值
        $queryArray = array_filter($queryArray, function ($v) {
            return $v !== null;
        });
        
        $main_body_ids = MainBody::getMainBodys($this->m_request['sessionToken']);
        if(count($main_body_ids)==0){
        	$result = ['page'=>$this->getDataByArray($params, 'page'), 'limit'=>$this->getDataByArray($params, 'limit'), 'count'=>0, 'data'=>[]];
        	$this->packRet(ErrMsg::RET_CODE_SUCCESS, $result);
        	return;
        }
       	$queryArray['loan_main_body_uuids'] = $main_body_ids;
        $obj = new LoanTransfer();
		$ret = $obj->details($queryArray,
				'f.* , o.order_num ',
			$this->getDataByArray($params, 'page'),$this->getDataByArray($params, 'limit'));

        $list = $ret['data'];
		if(is_array($list)&&count($list)>0){
            $ids = array_column($list, 'uuid');
//             $list = MapUtil::getMapdArrayByParams($list , 'transfer_status' , 'transfer_status');
//             $list = MapUtil::getMapdArrayByParams($list , 'loan_status' , 'loan_status');

            $list = MainBody::changeUuidToName($list , 'loan_main_body_uuid' , 'loan_main_body');
            $list = MainBody::changeUuidToName($list , 'collect_main_body_uuid' , 'collect_main_body');
			//获取当前节点权限
			$wy_list = array();
			$yq_list = array();
			foreach($list as $l){
				if($l['real_pay_type']==1){
					$wy_list[] = $l['uuid'];
				}else{
					$yq_list[] = $l['uuid'];
				}
			}
			//$map = array();
			$flow_code = 'loan_transfer_pay_type_1_code,loan_transfer_pay_type_2_code';
			$res = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList',
					['flow_code'=>$flow_code,'instance_id'=>implode(',' , $wy_list ),'sessionToken'=>$this->m_request['sessionToken']]);
			$flowInfos = $res['data'];
			$map = array();
			foreach($flowInfos as $flowInfo){
				$map[$flowInfo['instance_id']] = $flowInfo;
			}
		
			foreach($list as &$tran){
				//uuid列表
				if(isset($map[$tran['uuid']])){
					$tran['cur_node_auth'] = $map[$tran['uuid']]['cur_node_auth'];
				}
			}
		}
        $ret['data'] = $list;
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
}
