<?php
/**
 * 注销/启用产品
 */
use money\service\BaseService;
use money\model\MoneyProduct;

class StatusProduct extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'uuid' => 'require',
        'status' => 'require|in:1,2',
    ];

    public function exec(){
        $uuid = $this->m_request['uuid'];
        $status = $this->m_request['status'];
        $productDb = new MoneyProduct();
        $aff_raw = $productDb->statusProduct($uuid, $status);
        if($aff_raw != 1){
            throw new \Exception('处理失败', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['uuid'=>$uuid]);
    }
}