<?php
/**
 * 角色权限更新
 */
use money\service\BaseService;
use money\model\SysRole;

class RoleAuthUpdate extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'role_uuid' => 'require',
        'module_uuids' => 'array',
    ];

    public function exec(){
        $uuid = $this->m_request['role_uuid'];
        $data = $this->m_request['module_uuids'];
        $roleDb = new SysRole();
        $uuid = $roleDb->saveRoleAuth($data, $uuid);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['role_uuid'=>$uuid]);            
    }
}