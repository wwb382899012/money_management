<?php
/**
 * 数据字典列表
 * @author sun
 * @since 2018-03-28
 */
use money\service\BaseService;
use money\model\DataDict;

class DictList extends BaseService {

    protected $rule = [
        'sessionToken' => 'require',
        'page' => 'integer',
        'limit' => 'integer',
    ];

	public function exec(){
		$params = $this->m_request;
        isset($params['dict_type']) && $queryArray['dict_type'] = $params['dict_type'];
        isset($params['dict_desc']) && $queryArray['dict_desc'] = $params['dict_desc'];
	
		$obj = new DataDict();
		$ret = $obj->details($queryArray,' uuid,dict_type,dict_desc'
				,$this->getDataByArray($params, 'page'),$this->getDataByArray($params, 'limit'));
	
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
}