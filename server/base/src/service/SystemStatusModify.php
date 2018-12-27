<?php
/**
 * 业务系统状态变更
 * @author sun
 * @since 2018-03-28
 */
use money\service\BaseService;
use money\model\InterfacePriv;

class SystemStatusModify extends BaseService{

	protected $rule = [
        'sessionToken'=>'require',
        'uuid'=>'require',
        'status'=>'require|integer',
    ];

	public function exec() {
		$obj = new InterfacePriv();
		$params['uuid'] = $this->m_request['uuid'];
		if($this->m_request['status']==3){
			$params['is_delete'] = 2;
		}else{
			$params['status'] = $this->m_request['status'];
		}
        $obj->save($params, ['uuid' => $params['uuid']]);
	
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, null);
	}
}