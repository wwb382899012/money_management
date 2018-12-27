<?php
use money\service\BaseService;
use money\model\BankAccount;
use money\model\PayTransfer;

class PayRetry extends BaseService {

    protected $rule = [
        //'sessionToken' => 'require',
        'uuid' => 'require',
    ];

	/* (non-PHPdoc)
	 * @see BaseService::exec()
	 */
	protected function exec() {
		$uuid = $this->m_request['uuid'];
		$obj = new PayTransfer();
		
		$tranInfo = $obj->getDataById($uuid);
		if(!is_array($tranInfo)||count($tranInfo)==0){
			throw new Exception('数据不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		
		if($tranInfo['pay_status']!=PayTransfer::PAY_STATUS_UNPAID
			&&$tranInfo['pay_status']!=PayTransfer::PAY_STATUS_FAIL){
			throw new Exception('打款中或者打款成功无法重复打款',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}
		
		if(!isset($this->m_request['ukPwd'])){
			throw new Exception('加密狗密码不能为空',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}
		
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.layer.SessionGet", array('sessionToken'=>$this->m_request['sessionToken']));
		if(!isset($ret['code'])||$ret['code']!=0||empty($ret['data'])){
			throw new Exception('无法获取用户信息',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}
		$userInfo = $ret['data'];
		
		$array = array(
			'uuid'=>$uuid,
			'pay_status'=>PayTransfer::PAY_STATUS_PAYING
		);
		$obj->params = $array;
		$obj->saveOrUpdate();
		
		$account = new BankAccount();
		$pay_account = $account->getDataById($tranInfo['pay_account_uuid']);
		$collect_account = $account->getDataById($tranInfo['collect_account_uuid']);
		
		$payInfo = array(
				'trade_type'=>$tranInfo['transfer_pay_type'],
				'order_uuid'=>$tranInfo['transfer_num'],
				'pay_bank_account'=>$pay_account['bank_account'],
				'collect_bank_account'=>$tranInfo['collect_bank_account'],
				'to_name'=>$tranInfo['collect_account_name'],
				'to_bank_desc'=>$collect_account['bank_name'],
				'to_bank'=>$collect_account['bank_dict_key'],
				'to_city_name'=>$collect_account['city_name'],
				'notice_url'=>'com.jyblife.logic.bg.order.NoticeResult',
				'jmgUserName'=>$userInfo['username'],
				'jmgPassWord'=>$this->m_request['ukPwd'],
				'amount'=>$tranInfo['amount']
		);		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.pay.Order", $payInfo);
		if(empty($ret)||!isset($ret['code'])||$ret['code']!=0){
			throw new Exception("重试接口调用错误",ErrMsg::RET_CODE_SERVICE_FAIL);
		}
		$this->packRet(ErrMsg::RET_CODE_SUCCESS);
	}
}