<?php
use money\service\OrderBaseService;
use money\model\BankAccount;
use money\model\MainBody;
class AccountQuery extends OrderBaseService{
	protected $rule = [
	//'sessionToken' => 'require',
		'system_flag' => 'require',
		'main_body_name' => 'require'
	];
	
	public function exec(){
		try{
			$mainBody = MainBody::getByName($this->m_request['main_body_name']);
			if(!isset($mainBody)||!isset($mainBody['uuid'])){
				throw new Exception('主体不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
			}
			
			$b = new BankAccount();
			$ret = $b->loadDatas(['main_body_uuid'=>$mainBody['uuid'],'is_delete'=>1,'status'=>1],'bank_name,short_name,bank_account,bank_dict_key,account_name');
			
			$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
		}catch(Exception $e){
			throw new Exception($e->getMessage()?$e->getMessage():'系统异常',$e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
		}
	}
}

?>