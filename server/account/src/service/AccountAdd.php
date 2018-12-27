<?php
/**
 * 	账户添加
 * 	@author sun
 */
use money\service\BaseService;
use money\model\BankAccount;
class AccountAdd  extends BaseService{

    protected $rule = [
        'sessionToken'=>'require',
        'main_body_uuid' => 'require',
        'short_name' => 'require',
        'bank_name' => 'require',
        'bank_account' => 'require',
        'bank_dict_key' => 'require',
        'account_name' => 'require',
        'interface_priv' => 'require',
        'city'=>'require',
        'city_name'=>'require',
        'real_pay_type'=>'require'
    ];

	public function exec(){
		$obj = new BankAccount();
        $obj->params = array(
			'main_body_uuid'=>$this->m_request['main_body_uuid'],
			'short_name'=>$this->m_request['short_name'],
			'bank_name'=>$this->m_request['bank_name'],
			'bank_account'=>$this->m_request['bank_account'],
			'bank_dict_key'=>$this->m_request['bank_dict_key'],
			'account_name'=>$this->m_request['account_name'],
			'province'=>$this->m_request['province'],
			'city'=>$this->m_request['city'],
        	'city_name'=>$this->m_request['city_name'],
			'area'=>$this->m_request['area'],
			'address'=>$this->getDataByArray($this->m_request,'address'),
			'interface_priv'=>$this->getDataByArray($this->m_request,'interface_priv'),
			'real_pay_type'=>$this->getDataByArray($this->m_request,'real_pay_type'),
			'single_pay_limit'=>$this->getDataByArray($this->m_request,'single_pay_limit'),
			'bank_link_code'=>$this->getDataByArray($this->m_request,'bank_link_code'),
        	'deal_status'=>0
		);
        //获取用户信息
        $sessionInfo = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.layer.SessionGet', ['sessionToken'=>$this->m_request['sessionToken']]);
        if(!isset($sessionInfo['code']) || $sessionInfo['code'] != '0' || !isset($sessionInfo['data']['user_id'])){
            $code = isset($sessionInfo['code']) ? $sessionInfo['code'] : ErrMsg::RET_CODE_SERVICE_FAIL;
            $msg = isset($sessionInfo['msg']) ? $sessionInfo['msg'] : '获取会话信息失败';
            throw new \Exception($msg, $code);
        }
        $obj->params['create_user_id'] = $sessionInfo['data']['user_id'];
        //验证是否存在重复账号
        if($obj->validateDulicate($this->m_request['bank_name'],$this->m_request['bank_account'],isset($this->m_request['uuid'])?$this->m_request['uuid']:null)){
        	throw new Exception(' 银行账号不能重复！', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
        }
        
        try{
        	$obj->startTrans();
	        if(isset($this->m_request['uuid'])){
	        	$obj->params['uuid'] = $this->m_request['uuid'];
	        	$obj->saveOrUpdate();
	        	$data['uuid'] = $this->m_request['uuid'];
	        }else{
	        	$data['uuid'] = $obj->saveOrUpdate();
	        }
			
			//审批流发起
			$params = array(
					"flow_code"=>'account_add_apply',
					"instance_id"=>$data['uuid'],
					"main_body_uuid"=>$this->m_request['main_body_uuid'],
					"sessionToken"=>$this->m_request['sessionToken'],
					"info"=>''
			);
			$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.flow.Start" , $params);	
			if(!isset($ret)||!isset($ret['code'])||$ret['code']!=0){
				throw new Exception( '审批流发起错误：'.$ret['msg'], ErrMsg::RET_CODE_SERVICE_FAIL);
			}	
			$obj->commit();
			$this->packRet(ErrMsg::RET_CODE_SUCCESS, $data);
        }catch(Exception $e){
        	$obj->rollback();
        	throw new Exception($e->getMessage(),$e->getCode()?$e->getCode():'');
        }
		
	}
}