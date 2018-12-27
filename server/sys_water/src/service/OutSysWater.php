<?php
/**
 * 新增外部系统流水
 */
use money\service\BaseService;
use money\model\SysTradeWater;

class OutSysWater extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'out_water_no' => 'require',
        'water_uuid' => 'require',
    ];

    public function exec(){
        $uuid = $this->m_request['water_uuid'];
        $data['out_water_no'] = $this->m_request['out_water_no'];
        $db = new SysTradeWater();
        $raws = $db->saveWater($data, $uuid);
        if($raws === null){
            throw new \Exception('交易流水不存在', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['raw'=>$raws]);
    }
}