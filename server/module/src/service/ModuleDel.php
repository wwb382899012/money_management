<?php
/**
 * 用户信息获取
 */
use money\service\BaseService;
use money\model\Module;

class ModuleDel extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'module_uuid' => 'require',
    ];

    public function exec(){
        $uuid = $this->m_request['module_uuid'];
       
        $moduleDb = new Module();
        $res = $moduleDb->delModule($uuid);

        $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['num' => $res]);
    }
}