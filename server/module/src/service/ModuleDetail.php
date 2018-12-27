<?php
/**
 * 用户信息获取
 */
use money\service\BaseService;
use money\model\Module;

class ModuleDetail extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'module_uuid' => 'require',
    ];

    public function exec(){
        $uuid = $this->m_request['module_uuid'];
        $moduleDb = new Module();

        $data = $moduleDb->moduleDetail($uuid);
        if(!$data){
            throw new \Exception('数据不存在', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $data);
    }
}