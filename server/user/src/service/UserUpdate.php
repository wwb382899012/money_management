<?php
/**
 * 用户信息更新
 */
use money\service\BaseService;
use money\model\SysUser;

class UserUpdate extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'user_id' => 'require',
    ];

    public function exec(){
        $sessionToken = $this->m_request['sessionToken'];
        $user_id = $this->m_request['user_id'];
        $roles_uuids = isset($this->m_request['roles_uuids']) ? $this->m_request['roles_uuids'] : '';
        $main_body_uuids = isset($this->m_request['main_body_uuids']) ? $this->m_request['main_body_uuids'] : '';
        $email = isset($this->m_request['email']) ? $this->m_request['email'] : null;
        $status = isset($this->m_request['status']) ? $this->m_request['status'] : null;

        $userDb = new SysUser();
        $userInfo = [];
        isset($email) && $userInfo['email'] = $email;
        isset($status) && $userInfo['status'] = $status;
        !empty($userInfo) && $userDb->updateInfo($user_id, $userInfo);
        $userDb->updateRole(explode(',', $roles_uuids), $user_id);
        $userDb->updateMainBody(explode(',', $main_body_uuids), $user_id);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, []);
    }
}