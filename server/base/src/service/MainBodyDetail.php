<?php
/**
 * 主题详情
 * @author sun
 *
 */
use money\service\BaseService;
use money\model\MainBody;

class MainBodyDetail extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'uuid' => 'require',
    ];
	
	public function exec()
	{
		$cols = "*";
		$obj = MainBody::getDataById($this->m_request['uuid'] , $cols);
		if(!$obj||!isset($obj['uuid']))
		{
			throw new Exception("查询结果为空" , ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $obj);
	}
}