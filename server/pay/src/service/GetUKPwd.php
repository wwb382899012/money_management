<?php
use money\service\BaseService;
use money\base\RSAUtil;
use money\model\SysUser;

class GetUKPwd extends BaseService{

    protected $rule = [
        //'sessionToken' => 'require',
        'user_account' => 'require',
    ];

	/* (non-PHPdoc)
	 * @see BaseService::exec()
	 */
	protected function exec() {
		$obj = new SysUser();
		$params = $obj->loadDatas(array('username'=>$this->m_request['user_account']));
		if(!is_array($params)||count($params)==0){
			throw new Exception('账户不存在',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}
		$p = $params[0];
		if(!isset($p['readPwd_1'])||!isset($p['readPwd_2'])){
			throw new Exception('用户秘钥信息不存在',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}
		
		$u = new RSAUtil();
		$ret = array(
			'readPwd_1'=>$u->privateDecrypt($p['readPwd_1']),
			'readPwd_2'=>$u->privateDecrypt($p['readPwd_2'])
		);
		
		$this->packRet(ErrMsg::RET_CODE_SUCCESS,$ret);
	}
}