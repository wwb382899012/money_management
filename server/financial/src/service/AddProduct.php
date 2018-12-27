<?php
/**
 * 新增理财产品
 */
use money\service\BaseService;
use money\model\MoneyProduct;

class AddProduct extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'product_name' => 'require',
        'bank_dict_value' => 'require',
    ];

    public function exec(){
        $sessionInfo = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.layer.SessionGet', ['sessionToken'=>$this->m_request['sessionToken']]);
        if(!isset($sessionInfo['code']) || $sessionInfo['code'] != '0' || !isset($sessionInfo['data']['user_id'])){
            $code = isset($sessionInfo['code']) ? $sessionInfo['code'] : ErrMsg::RET_CODE_SERVICE_FAIL;
            $msg = isset($sessionInfo['msg']) ? $sessionInfo['msg'] : '获取会话信息失败';
            throw new \Exception($msg, $code);
        }
        $data['product_name'] = $this->m_request['product_name'];
        $data['bank_dict_value'] = $this->m_request['bank_dict_value'];
        $data['create_user_id'] = isset($sessionInfo['data']['user_id']) ? $sessionInfo['data']['user_id'] : 0;
        $data['create_name'] = isset($sessionInfo['data']['name']) ? $sessionInfo['data']['name'] : '';
        isset($this->m_request['annual_income_rate']) && $data['annual_income_rate'] = $this->m_request['annual_income_rate'];
        $productDb = new MoneyProduct();
        $uuid = $productDb->saveProduct($data);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['uuid'=>$uuid]);
    }
}