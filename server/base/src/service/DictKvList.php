<?php
/**
 * 数据字典键值列表
 * @author sun
 * @since 2018-03-28
 */
use money\service\BaseService;
use money\model\DataDictKv;

class DictKvList extends BaseService{

    protected $rule = [
        'page' => 'integer',
        'limit' => 'integer',
    ];

	public function exec(){
		$params = $this->m_request;
		!isset($params['page']) && $params['page'] = 1;
		!isset($params['limit']) && $params['limit'] = 50;
        $queryArray = [];
		isset($params['dict_type']) && $queryArray['dict_type'] = $params['dict_type'];
		isset($params['dict_desc']) && $queryArray['dict_desc'] = $params['dict_desc'];

		$obj = new DataDictKv();
		$ret = $obj->details($queryArray,' d.dict_type,d.dict_desc , v.uuid , v.dict_key,v.dict_value,v.index '
				,$this->getDataByArray($params, 'page'),$this->getDataByArray($params, 'limit'));
	
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
}