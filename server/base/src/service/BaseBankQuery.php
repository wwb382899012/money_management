<?php
/**
 * 基础数据查询
 * @author sun
 *
 */
use money\service\BaseService;

class BaseBankQuery extends BaseService {

    protected $rule = [
        'sessionToken'=>'require',
        'bank'=>'require',
    ];

	protected function exec() {
		$req = array(
			'bank'=>$this->m_request['bank']
		);
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.banklink.action.service.FindBaseData ", $req);
		if(!isset($ret['code'])||$ret['code']!=0){
			throw new Exception('接口调用异常' , ErrMsg::RET_CODE_SERVICE_FAIL);
			return;
		}
		$this->packRet(ErrMsg::RET_CODE_SUCCESS,$ret['data']);
	}
}