<?php

use money\service\BaseService;
use money\model\SysTradeWater;
class ConfirmResult extends BaseService{
	protected $rule = [
		//'sessionToken' => 'require',
		'order_uuid' => 'require',
		'status' => 'require'
	];
	
	public function exec(){
		$obj = new SysTradeWater();
		$data = $obj->loadDatas(['order_uuid'=>$this->m_request['order_uuid'],'status'=>SysTradeWater::STATUS_WAIT_CONFIRM]);
		
		if(!is_array($data)||count($data)==0){
			throw new \Exception('data not exists',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}
		
		$status = $this->m_request['status']==1?SysTradeWater::STATUS_SUCCESS:SysTradeWater::STATUS_FAIL;
		$obj->params = [
			'uuid'=>$data[0]['uuid'],
			'status'=>$status
		];
		$obj->saveOrUpdate();
		$this->packRet(ErrMsg::RET_CODE_SUCCESS);
	}
}

?>