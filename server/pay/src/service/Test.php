<?php
use money\service\BaseService;

class Test extends BaseService{

	/* (non-PHPdoc)
	 * @see BaseService::exec()
	 */
	protected function exec() {
		$array = array(
			'trade_type'=>'required',
			'order_uuid'=>'required',
			'pay_bank_account'=>'required',
			'collect_bank_account'=>'required',
			'notice_url'=>'required',
			'jmgUserName'=>'required',
			'jmgPassWord'=>'required',
			'amount'=>'required,num'
		);
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.pay.Order", $array);
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, json_encode($ret));
	}
}