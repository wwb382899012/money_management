<?php
/**
*	付款指令页列表
*	@author sun
*	@since 2018-03-11
*/
use money\service\BaseService;
use money\base\MapUtil;
use money\model\PayOrder;
use money\model\MainBody;
class PayOrderList extends BaseService
{
    protected $rule = [
        'sessionToken' => 'require',
    ];

	public function exec()
	{
		$params = $this->m_request;
		$queryArray = array(
            'order_create_people'=>$this->getDataByArray($params, 'order_create_people'),
            'order_status'=>$this->getDataByArray($params, 'order_status'),
            'apply_begin_time'=>$this->getDataByArray($params, 'apply_begin_time'),
            'apply_end_time'=>$this->getDataByArray($params, 'apply_end_time'),
            'approve_begin_time'=>$this->getDataByArray($params, 'approve_begin_time'),
            'approve_end_time'=>$this->getDataByArray($params, 'approve_end_time'),
            'pay_main_body_uuid'=>$this->getDataByArray($params, 'pay_main_body_uuid'),
            'collect_main_body_uuid'=>$this->getDataByArray($params, 'collect_main_body_uuid'),
			'out_order_num'=>$this->getDataByArray($params, 'out_order_num'),
            'is_financing'=>$this->getDataByArray($params, 'is_financing')
        );
		
		$main_body_ids = MainBody::getMainBodys($this->m_request['sessionToken']);
		if(count($main_body_ids)==0){
			$result = ['page'=>$this->getDataByArray($params, 'page'), 'limit'=>$this->getDataByArray($params, 'limit'), 'count'=>0, 'data'=>[]];
			$this->packRet(ErrMsg::RET_CODE_SUCCESS, $result);
			return;
		}		
		$queryArray['pay_main_body_uuids'] = $main_body_ids;
		
		//过滤掉NULL值
        $queryArray = array_filter($queryArray, function ($v) {
			return $v !== null;
		});
		$obj = new PayOrder();
		$ret = $obj->orderDetails($queryArray,'*',$this->getDataByArray($params, 'page')
				,$this->getDataByArray($params, 'limit'));

		$list = $ret['data'];
		if(is_array($list)&&count($list)>0){
			//转化字典，主体
			$ids = array_column($list, 'uuid');
            $list = MapUtil::getMapdArrayByParams($list , 'order_pay_type' , 'pay_type');
//             $list = MapUtil::getMapdArrayByParams($list , 'pay_status' , 'pay_status');
//             $list = MapUtil::getMapdArrayByParams($list , 'is_financing' , 'is_financing');
			$list = MainBody::changeUuidToName($list , 'pay_main_body_uuid' , 'pay_main_body');
// 			$list = MainBody::changeUuidToName($list , 'collect_main_body_uuid' , 'collect_main_body');
			//获取当前节点权限
            $res = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList',
					 ['flow_code'=>'pay_order','instance_id'=>implode(',' , $ids ),'sessionToken'=>$this->m_request['sessionToken']]);
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