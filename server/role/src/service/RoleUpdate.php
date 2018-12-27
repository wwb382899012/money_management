<?php
/**
 * 角色信息获取
 */
use money\service\BaseService;
use money\model\SysRole;

class RoleUpdate extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'role_uuid' => 'require',
        'name' => 'require',
        'status' => 'require|in:1,2',
    ];

    public function exec(){
        $uuid = $this->m_request['role_uuid'];
        $data['name'] = isset($this->m_request['name']) ? $this->m_request['name'] : null;
        $data['info'] = isset($this->m_request['info']) ? $this->m_request['info'] : null;
        $data['status'] = isset($this->m_request['status']) ? $this->m_request['status'] : null;
        $roleDb = new SysRole();
        if($roleDb->validateDulicate($this->m_request['name'],null)){
        	throw new Exception('角色名称不能重复！', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
        }
        $uuid = $roleDb->saveRole($data, $uuid);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['role_uuid'=>$uuid]);            
    }
}