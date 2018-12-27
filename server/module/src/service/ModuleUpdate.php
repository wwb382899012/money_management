<?php
/**
 * 用户信息获取
 */
use money\service\BaseService;
use money\model\Module;

class ModuleUpdate extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'module_uuid' => 'require',
        'name' => 'require',
        'sort' => 'require|integer',
        'status' => 'require|in:0,1',
        //'api_url' => 'require',
        //'son_api' => 'require',
        'is_menu' => 'require|in:1,2',
    ];

    public function exec(){
        $uuid = $this->m_request['module_uuid'];
        $data['name'] = $this->m_request['name'];
        $data['sort'] = $this->m_request['sort'];
        $data['status'] = $this->m_request['status'];
        $data['api_url'] = $this->m_request['api_url'];
        $data['son_api'] = $this->m_request['son_api'];
        $data['is_menu'] = $this->m_request['is_menu'];
        if(isset($this->m_request['module_pid_uuid']) && $this->m_request['module_pid_uuid']){
            $data['pid_uuid'] = $this->m_request['module_pid_uuid'];
        }        
        $moduleDb = new Module();
        $uuid = $moduleDb->saveModule($data, $uuid);
        if($uuid){
            $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['uuid'=>$uuid]);            
        }else{
            throw new \Exception('更新失败数据不存在', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
    }
}