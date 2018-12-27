<?php
/**
 * 角色信息获取
 */
use money\service\BaseService;
use money\model\SysRole;

class RoleList extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'page' => 'integer',
        'limit' => 'integer',
    ];

    public function exec(){
        $page = isset($this->m_request['page']) ? $this->m_request['page'] : 1;
        $limit = isset($this->m_request['limit']) ? $this->m_request['limit'] : 50;
        $name = isset($this->m_request['name']) ? $this->m_request['name'] : '';
        $roleDb = new SysRole();
        $data = $roleDb->listData($page, $limit, $name);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $data);            
    }
}