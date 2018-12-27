<?php
use money\service\BaseService;

class Test extends BaseService{

	/* (non-PHPdoc)
	 * @see BaseService::exec()
	 */
	protected function exec() {
		$array = array(
			'trade_type'=>'1',
			'order_uuid'=>'112',
			'pay_bank_account'=>'622908493458092716',
			'collect_bank_account'=>'622908493458092716',
			'to_name'=>'aa',
			'to_bank_desc'=>'test',
			'to_bank'=>'1',
			'to_city_name'=>'a',
			'notice_url'=>'com.jyblife.logic.bg.order.NoticeResult',
			'jmgUserName'=>'yuqidi',
			'jmgPassWord'=>'yuqidi',
			'amount'=>'100',
			'purPose'=>'付款'
		);
		//'sessionToken' => 'require',
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.pay.Order", $array);
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, json_encode($ret));
	}
}