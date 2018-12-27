<?php
/**
 * 对账列表
 * @author sun
 *
 */
use money\service\BaseService;
use money\model\BankAccount;
use money\model\MainBody;

class CheckList extends BaseService{

    protected $rule = [
        'sessionToken'=>'require',
        'page' => 'integer',
        'limit' => 'integer',
    ];

	public function exec(){
	    !isset($this->m_request['page']) && $this->m_request['page'] = 1;
	    !isset($this->m_request['limit']) && $this->m_request['limit'] = 50;
		$params = $this->m_request;
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $params);
        return;
		$queryArray = array(
				'main_body_uuid'=>$this->getDataByArray($params, 'main_body_uuid'),
				'bank_name'=>$this->getDataByArray($params, 'bank_name'),
				'real_pay_type'=>$this->getDataByArray($params, 'real_pay_type'),
				'is_delete'=>'1'
		);
		if(isset($this->m_request['status'])){
			$queryArray['status'] = $this->m_request['status'];
		}
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
		$obj = new BankAccount();
		$ret = $obj->details($queryArray,' * '
				,$this->getDataByArray($params, 'page'),$this->getDataByArray($params, 'limit'));
        $ret['data'] = MainBody::changeUuidToName($ret['data'] , 'main_body_uuid' , 'main_body_name');
        if(count($ret['data'])==0){
        	$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
        	return;
        }
        $add_map = array();
        $ids = array_column($ret['data'], 'uuid');

        $res = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList',
        		['flow_code'=>'account_add_apply','instance_id'=>implode(',' , $ids ),'sessionToken'=>$this->m_request['sessionToken']]);
        if(isset($res['data'])&&count($res['data'])>0){
	       	$flowInfos = $res['data'];
	        foreach($flowInfos as $flowInfo){
	        	$add_map[$flowInfo['instance_id']] = $flowInfo;
	        }
	        foreach($ret['data'] as &$account){
	        	//uuid列表
	        	if(isset($add_map[$account['uuid']])){
	        		$account['add_flow_info'] = $add_map[$account['uuid']];
	        	}
	        }
        }
        	
        $del_map = array();
        $res = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList',
        		['flow_code'=>'account_del_apply','instance_id'=>implode(',' , $ids ),'sessionToken'=>$this->m_request['sessionToken']]);
        if(isset($res['data'])&&count($res['data'])>0){
	        $flowInfos = $res['data'];
	        foreach($flowInfos as $flowInfo){
	        	$del_map[$flowInfo['instance_id']] = $flowInfo;
	        }
	        foreach($ret['data'] as &$account){
	        	//uuid列表
	        	if(isset($del_map[$account['uuid']])){
	        		$account['del_flow_info'] = $del_map[$account['uuid']];
	        	}
	       	}
        }
        
        $enable_map = array();
        $res = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList',
        		['flow_code'=>'account_enable_apply','instance_id'=>implode(',' , $ids ),'sessionToken'=>$this->m_request['sessionToken']]);
        if(isset($res['data'])&&count($res['data'])>0){
        	$flowInfos = $res['data'];
        	foreach($flowInfos as $flowInfo){
        		$enable_map[$flowInfo['instance_id']] = $flowInfo;
        	}
        	foreach($ret['data'] as &$account){
        		//uuid列表
        		if(isset($enable_map[$account['uuid']])){
        			$account['enable_flow_info'] = $enable_map[$account['uuid']];
        		}
        	}
        }
         
        $update_map = array();
        $res = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList',
        		['flow_code'=>'account_update_apply','instance_id'=>implode(',' , $ids ),'sessionToken'=>$this->m_request['sessionToken']]);
        if(isset($res['data'])&&count($res['data'])>0){
	        $flowInfos = $res['data'];
	        foreach($flowInfos as $flowInfo){
	        	$update_map[$flowInfo['instance_id']] = $flowInfo;
	        }
	        foreach($ret['data'] as &$account){
	        	//uuid列表
	        	if(isset($update_map[$account['uuid']])){
	        		$account['update_flow_info'] = $update_map[$account['uuid']];
	        	}
        	}
        }
        
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
}