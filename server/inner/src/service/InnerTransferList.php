<?php
/**
 * 内部调拨列表
 * @author sun
 * @since 2018-04-25
 */
use money\service\BaseService;
use money\model\MainBody;
use money\model\InnerTransfer;
class InnerTransferList extends BaseService {

    protected $rule = [
        'sessionToken' => 'require',
    ];

	public function exec()
	{
		$params = $this->m_request;
		$queryArray = array(
				'main_body_uuid'=>$this->getDataByArray($params, 'main_body_uuid'),
				'apply_begin_time'=>$this->getDataByArray($params, 'apply_begin_time'),
				'apply_end_time'=>$this->getDataByArray($params, 'apply_end_time'),
				'approve_begin_time'=>$this->getDataByArray($params, 'approve_begin_time'),
				'approve_end_time'=>$this->getDataByArray($params, 'approve_end_time'),
				'transfer_status'=>$this->getDataByArray($params, 'transfer_status'),
				'pay_status'=>$this->getDataByArray($params, 'pay_status')
		);
		$main_body_ids = MainBody::getMainBodys($this->m_request['sessionToken']);
		if(count($main_body_ids)==0){
			$result = ['page'=>$this->getDataByArray($params, 'page'), 'limit'=>$this->getDataByArray($params, 'limit'), 'count'=>0, 'data'=>[]];
			$this->packRet(ErrMsg::RET_CODE_SUCCESS, $result);
			return;
		}
		$queryArray['main_body_uuids'] = $main_body_ids;
		//过滤掉NULL值
		$queryArray = array_filter($queryArray, function ($v) {
			return $v !== null;
		});
		$obj = new InnerTransfer();
		$ret = $obj->details($queryArray,'*,'
				.'(select bank_name from m_bank_account b where b.uuid = pay_account_uuid) pay_bank_name,(select bank_name from m_bank_account b where b.uuid = collect_account_uuid) collect_bank_name  ',$this->getDataByArray($params, 'page')
				,$this->getDataByArray($params, 'limit'));

        $list = $ret['data'];
        if(is_array($list)&&count($list)>0){
            $ids = array_column($list, 'uuid');
//             $list = MapUtil::getMapdArrayByParams($list , 'transfer_status' , 'transfer_status');
//             $list = MapUtil::getMapdArrayByParams($list , 'pay_status' , 'pay_status');

            $list = MainBody::changeUuidToName($list , 'main_body_uuid' , 'main_body_name');

			//获取当前节点权限
			$flowInfos = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList',
					['flow_code'=>'inner_transfer','instance_id'=>implode(',' , $ids ),'sessionToken'=>$this->m_request['sessionToken']]);
			$map = array();
			foreach($flowInfos as $flowInfo){
				$map[$flowInfo['instance_id']] = $flowInfo;
			}
		}
        $ret['data'] = $list;
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
}