<?php
/**
 * 业务系统新增或删除
 * @author sun
 * @since 2018-03-28
 */
use money\service\BaseService;
use money\model\InterfacePriv;

class SystemCreateOrUpdate extends BaseService {
	
    protected $rule = [
        'sessionToken'=>'require',
        'system_flag'=>'require',
        'sys_name'=>'require',
        'pwd_key'=>'require',
    ];

	public function exec(){
		//获取用户信息
		$sessionInfo = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.layer.SessionGet', ['sessionToken'=>$this->m_request['sessionToken']]);
        if(!isset($sessionInfo['code']) || $sessionInfo['code'] != '0' || !isset($sessionInfo['data']['user_id'])){
            $code = isset($sessionInfo['code']) ? $sessionInfo['code'] : ErrMsg::RET_CODE_SERVICE_FAIL;
            $msg = isset($sessionInfo['msg']) ? $sessionInfo['msg'] : '获取会话信息失败';
            throw new \Exception($msg, $code);
        }
	
		$obj = new InterfacePriv();
		$params = array(
				'system_flag'=>$this->m_request['system_flag'],
				'sys_name'=>$this->m_request['sys_name'],
				'pwd_key'=>$this->m_request['pwd_key'],
				'ip_address'=>$this->m_request['ip_address']
		);
		if(isset($this->m_request['uuid'])){
			$params['uuid'] = $this->m_request['uuid'];
		}
		$params['create_user_id'] = $sessionInfo['data']['user_id'];
		$params['create_user_name'] = $sessionInfo['data']['username'];
		$obj->params = $params;
		$obj->saveOrUpdate();
	
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, null);
	}
}