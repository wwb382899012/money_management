<?php
/**
 * 账户详情
 * @author sun
 *
 */
use money\service\BaseService;
use money\model\BankAccount;
use money\model\MainBody;
class AccountDetail extends BaseService{

    protected $rule = [
        'sessionToken'=>'require',
        'uuid' => 'require',
    ];

	public function exec()
	{
		$cols = "*";
        $obj = BankAccount::getDataById($this->m_request['uuid'] , $cols);
        //权限验证
        MainBody::validateAuth($this->m_request['sessionToken'], $obj['main_body_uuid']);
        
        $data = MainBody::changeUuidToName([$obj] , 'main_body_uuid' , 'main_body_name');
        $obj = $data[0];

        $res = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList',
        		['flow_code'=>'account_add_apply','instance_id'=>$this->m_request['uuid'],'sessionToken'=>$this->m_request['sessionToken']]);
        if(isset($res['data'])&&count($res['data'])>0){
        	$flowInfos = $res['data'][0];
        	$obj['add_flow_info'] = $flowInfos;
        }
        
        $res = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList',
        		['flow_code'=>'account_del_apply','instance_id'=>$this->m_request['uuid'],'sessionToken'=>$this->m_request['sessionToken']]);
        if(isset($res['data'])&&count($res['data'])>0){
        	$flowInfos = $res['data'][0];
        	$obj['del_flow_info'] = $flowInfos;
        }
        
        $res = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList',
        		['flow_code'=>'account_enable_apply','instance_id'=>$this->m_request['uuid'],'sessionToken'=>$this->m_request['sessionToken']]);
        if(isset($res['data'])&&count($res['data'])>0){
        	$flowInfos = $res['data'][0];
        	$obj['enable_flow_info'] = $flowInfos;
        }
        
        $res = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList',
        		['flow_code'=>'account_update_apply','instance_id'=>$this->m_request['uuid'],'sessionToken'=>$this->m_request['sessionToken']]);
        if(isset($res['data'])&&count($res['data'])>0){
        	$flowInfos = $res['data'][0];
        	$obj['update_flow_info'] = $flowInfos;
        }
		
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $obj);
	}
}