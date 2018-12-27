<?php
/**
 * 角色信息获取
 */
use money\service\BaseService;
use money\model\SysRole;

class RoleDel extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'role_uuid' => 'require',
    ];

    public function exec(){
        $uuid = $this->m_request['role_uuid'];
        $roleDb = new SysRole();
        $roleDb->delRole($uuid);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, []);            
    }
}