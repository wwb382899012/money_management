<?php
/**
 * 用户信息获取
 */
use money\service\BaseService;
use money\model\SysUser;

class UserList extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'role_uuid' => 'array',
        'main_body_uuid' => 'array',
        'page' => 'integer',
        'limit' => 'integer',
    ];

    public function exec(){
        $limit = isset($this->m_request['limit']) ? $this->m_request['limit'] : 50;
        $page = isset($this->m_request['page']) ? $this->m_request['page'] : 1;
        $sessionToken = $this->m_request['sessionToken'];
        $roleIds = isset($this->m_request['role_uuid']) ? $this->m_request['role_uuid'] : [];
        $username = isset($this->m_request['username']) ? $this->m_request['username'] : '';
        $bodys = isset($this->m_request['main_body_uuid']) ? $this->m_request['main_body_uuid'] : [];

        $userDb = new SysUser();
        $userInfo = $userDb->getUserList($page, $limit, $username, $roleIds, $bodys);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $userInfo);
    }
}