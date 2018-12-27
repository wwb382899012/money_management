<?php
/**
 * 数据字典键值删除
 * @author sun
 * @since 2018-03-28
 */
use money\service\BaseService;
use money\model\DataDictKv;

class DictKvDel extends BaseService{
	
    protected $rule = [
        'sessionToken' => 'require',
        'uuid' => 'require',
    ];

	public function exec() {
		$obj = new DataDictKv();
		$obj->del($this->m_request['uuid']);

		$this->packRet(ErrMsg::RET_CODE_SUCCESS, null);
	}
}