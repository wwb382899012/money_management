<?php
/**
 * 角色信息获取
 */
use money\service\BaseService;
use money\model\SysRole;

class RoleDetail extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'role_uuid' => 'require',
    ];

    public function exec(){
        $uuid = $this->m_request['role_uuid'];
        $roleDb = new SysRole();
        $data = $roleDb->detail($uuid);
        if(!$data){
            throw new \Exception('获取数据失败', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $data);            
    }
}