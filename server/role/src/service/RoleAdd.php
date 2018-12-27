<?php
/**
 * 角色信息获取
 */
use money\service\BaseService;
use money\model\SysRole;

class RoleAdd extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'name' => 'require',
        'status' => 'require|in:1,2',
    ];

    public function exec(){
        $data['name'] = $this->m_request['name'];
        $data['info'] = isset($this->m_request['info']) ? $this->m_request['info']:'';
        $roleDb = new SysRole();
        if($roleDb->validateDulicate($this->m_request['name'],null)){
        	throw new Exception('角色名称不能重复！', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
        }
        $uuid = $roleDb->saveRole($data);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['role_uuid'=>$uuid]);            
    }
}