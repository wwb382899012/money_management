<?php
use money\service\BaseService;
use money\model\SysUser;

class SetUKPwd extends BaseService{

    protected $rule = [
        //'sessionToken' => 'require',
        'user_account' => 'require',
        'readPwd_1' => 'require',
        'readPwd_2' => 'require',
    ];

	protected function exec() {
		$obj = new SysUser();
		$params = $obj->loadDatas(array('username'=>$this->m_request['user_account']));
		if(!is_array($params)||count($params)<=0){
			throw new Exception('账户不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		$p = array();
		$p['uuid'] = $params[0]['uuid'];
		$p['readPwd_1'] = $this->m_request['readPwd_1'];
		$p['readPwd_2'] = $this->m_request['readPwd_2'];
		$obj->params = $p;
		$obj->saveOrUpdate();
		
		$this->packRet(ErrMsg::RET_CODE_SUCCESS);
	}
}