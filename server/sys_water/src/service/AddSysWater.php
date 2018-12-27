<?php
/**
 * 新增系统流水
 */
use money\service\BaseService;
use money\model\SysTradeWater;

class AddSysWater extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'trade_type' => 'require',
        'pay_account_uuid' => 'require',
        'pay_bank_key' => 'require',
        'pay_bank_account' => 'require',
        'amount' => 'require',
        'currency' => 'require',
        'status'=>'require'
    ];

    public function exec(){
        $data = $this->m_request;
        $sessionToken = $data['sessionToken'];
        unset($data['sessionToken']);
        $db = new SysTradeWater();
        $uuid = $db->addWater($data);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['uuid'=>$uuid]);
    }
}