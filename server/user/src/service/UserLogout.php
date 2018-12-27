<?php
/**
 * web ajax http 请求接入
 */
use money\service\BaseService;

class UserLogout extends BaseService{

    protected $rule = [
        'sessionToken' => 'require|alphaNum',
    ];

    public function exec(){
        $SessionLogic = new SessionLogic();
        $sessionToken = $this->m_request['sessionToken'];
        if (!$SessionLogic->userLogout($sessionToken)) {
            $this->packRet(ErrMsg::RET_CODE_DATA_INVAILD, '会话验证失败');
            return;
        }

        $this->packRet(ErrMsg::RET_CODE_SUCCESS);
    }
}