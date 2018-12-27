<?php
/**
 * 作废流水
 */
use money\service\BaseService;
use money\model\SysTradeWater;

class InvalidWater extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'water_uuid' => 'require',
    ];

    public function exec(){
        $uuid = $this->m_request['water_uuid'];
        $data['is_effective'] = 2;
        $db = new SysTradeWater();
        $raws = $db->saveWater($data, $uuid);
        if($raws === null){
            throw new \Exception('交易流水不存在', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['raw'=>$raws]);
    }
}