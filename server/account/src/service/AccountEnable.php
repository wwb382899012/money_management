<?php
/**
 * 账户启用
 * @author sun
 *
 */
use money\service\BaseService;
use money\model\BankAccount;

class AccountEnable  extends BaseService{
	protected $rule = [
        'sessionToken'=>'require',
        'uuid' => 'require',
    ];

	public function exec()
	{
        $obj = new BankAccount();
        $obj->save(['deal_status'=>0], [$obj->getPk()=>$this->m_request['uuid'] ]);
        
       //审批流发起
        $info = $obj->getDataById($this->m_request['uuid']);
		$params = array(
				"flow_code"=>'account_enable_apply',
				"instance_id"=>$this->m_request['uuid'],
				"main_body_uuid"=>$info['main_body_uuid'],
				"sessionToken"=>$this->m_request['sessionToken'],
				"info"=>''
		);
		
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.flow.Start" , $params);
		
		$this->packRet(ErrMsg::RET_CODE_SUCCESS);
	}}

?>