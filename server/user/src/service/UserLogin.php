<?php
/**
 * web ajax http 请求接入
 */
use money\service\BaseService;

class UserLogin extends BaseService{

    protected $rule = [
        //'sessionToken' => 'require',
        'username' => 'require',
        'password' => 'require',
    ];

    public function exec(){
        $SessionLogic = new SessionLogic();
        $username = $this->m_request['username'];
        $password = $this->m_request['password'];
        //$sessionToken = $this->m_request['sessionToken'];
        $remote_addr = isset($this->m_request['remote_addr']) ? $this->m_request['remote_addr'] : '';

        $userInfo = $SessionLogic->userLogin($username, $password);
        if(!$userInfo){
            throw new \Exception('登陆失败', ErrMsg::RET_CODE_LOGIN_FAILED);
        }
        $token = $SessionLogic->setSession($userInfo['identifier'] , $username, $remote_addr);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['token'=>$token,'user_id'=>$userInfo['identifier']]);
    }
}