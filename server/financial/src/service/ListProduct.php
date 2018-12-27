<?php
/**
 * 新增理财产品
 */
use money\service\BaseService;
use money\model\MoneyProduct;

class ListProduct extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'page' => 'number',
        'limit' => 'number'
    ];

    public function exec(){
        $page = $this->m_request['page'];
        $limit = $this->m_request['limit'];
        $where = [];
        if(isset($this->m_request['product_name'])){
            $where['product_name'] = $this->m_request['product_name'];
        }
        if(isset($this->m_request['bank_dict_value'])){
            $where['bank_dict_value'] = $this->m_request['bank_dict_value'];
        }
        if(isset($this->m_request['status'])){
        	$where['status'] = $this->m_request['status'];
        }

        $productDb = new MoneyProduct();
        $result = $productDb->listData($page, $limit, $where);
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $result);
    }
}