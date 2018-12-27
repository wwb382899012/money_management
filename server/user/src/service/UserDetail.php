<?php
/**
 * Class UserDetail
 */
use money\service\BaseService;
use money\model\SysUser;

class UserDetail extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'user_id' => 'integer|requireIf:user_account,',
    ];

    public function exec(){
        $userId = isset($this->m_request['user_id']) ? $this->m_request['user_id'] : null;
        $userName = isset($this->m_request['user_account']) ? $this->m_request['user_account'] : null;
        $auth = isset($this->m_request['auth']) ? $this->m_request['auth'] : null;
        $userDb = new SysUser();
        $userInfo = $userDb->userDetail($userId, $userName, $auth);
        if(!$userInfo){
            throw new \Exception('获取数据失败', ErrMsg::RET_CODE_DATA_INVAILD);
        }
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $userInfo);
    }
}