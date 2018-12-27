<?php
/**
 * 编辑理财产品
 */
use money\service\BaseService;
use money\model\MoneyProduct;

class UpdateProduct extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'uuid' => 'require',
        'product_name' => 'require',
        'bank_dict_value' => 'require',
        'status' => 'require|in:1,2',
    ];

    public function exec(){
        $data['product_name'] = $this->m_request['product_name'];
        $data['bank_dict_value'] = $this->m_request['bank_dict_value'];
        $data['status'] = $this->m_request['status'];
        isset($this->m_request['annual_income_rate']) && $data['annual_income_rate'] = $this->m_request['annual_income_rate'];
        $productDb = new MoneyProduct();
        $uuid = $productDb->saveProduct($data, $this->m_request['uuid']);
        if(!$uuid){
            throw new \Exception('更新失败', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['uuid'=>$uuid]);
    }
}