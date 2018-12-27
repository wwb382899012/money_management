<?php

use money\service\BaseService;
use money\model\BankAccount;
use money\model\InnerTransfer;

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
		$obj = new InnerTransfer();
		
		$tranInfo = $obj->getDataById($uuid);
		if(!is_array($tranInfo)||count($tranInfo)==0){
			throw new Exception('数据不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		
		if($tranInfo['pay_status']!=InnerTransfer::INNER_STATUS_UNPAID
			&&$tranInfo['pay_status']!=InnerTransfer::INNER_STATUS_FAIL){
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
		
		try{
            $obj->startTrans();
			$array = array(
				'uuid'=>$uuid,
				'pay_status'=>InnerTransfer::INNER_STATUS_PAYING
			);
			$obj->params = $array;
			$obj->saveOrUpdate();
			
			$account = new BankAccount();
			$pay_account = $account->getDataById($tranInfo['pay_account_uuid']);
			
			$payInfo = array(
					'trade_type'=>15,
					'order_uuid'=>$tranInfo['order_num'],
					'pay_bank_account'=>$pay_account['bank_account'],
					'collect_bank_account'=>$tranInfo['collect_bank_account'],
					'to_name'=>$tranInfo['collect_account_name'],
					'to_bank_desc'=>$tranInfo['collect_bank_name'],
					'to_bank'=>$tranInfo['collect_bank'],
					'to_city_name'=>$tranInfo['collect_city_name'],
					'notice_url'=>'com.jyblife.logic.bg.inner.NoticeResult',
					'jmgUserName'=>$userInfo['username'],
					'jmgPassWord'=>$this->m_request['ukPwd'],
					'amount'=>$tranInfo['amount']
			);
			$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.pay.Order", $payInfo);
			if(empty($ret)||!isset($ret['code'])||$ret['code']!=0){
				throw new Exception("重试接口调用错误",ErrMsg::RET_CODE_SERVICE_FAIL);
			}
            $obj->commit();
		}catch(Exception $e){
            $obj->rollback();
			throw new Exception('重试接口调用错误',$e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
		}
		$this->packRet(ErrMsg::RET_CODE_SUCCESS);
	}
}

?>