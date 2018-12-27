<?php
/**
 * 用户信息获取
 */
use money\service\BaseService;
use money\model\Module;

class ModuleList extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'is_menu' => 'integer|in:1,2',
        'status' => 'integer|in:0,1',
        'page' => 'integer',
        'limit' => 'integer',
    ];

    public function exec(){
        $where = [];
        if(isset($this->m_request['name']) && $this->m_request['name']){
            $where['name'] = $this->m_request['name'];
        }
        if(isset($this->m_request['is_menu']) && in_array($this->m_request['is_menu'], [1,2])){
            $where['is_menu'] = $this->m_request['is_menu'];
        }
        if(isset($this->m_request['status']) && in_array($this->m_request['status'], [0,1])){
            $where['status'] = $this->m_request['status'];
        }
        $moduleDb = new Module();
        $data = $moduleDb->getModule($where);

        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $data);
    }
}