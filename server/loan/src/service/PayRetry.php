<?php
use money\service\BaseService;
use money\model\BankAccount;
use money\model\LoanCashFlow;
use money\model\LoanTransfer;

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
		$obj = new LoanTransfer();
		
		$tranInfo = $obj->getDataById($uuid);
		if(!is_array($tranInfo)||count($tranInfo)==0){
			throw new Exception('数据不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		
		if($tranInfo['loan_status']!=LoanTransfer::LOAN_STATUS_UNPAID
			&&$tranInfo['loan_status']!=LoanTransfer::LOAN_STATUS_FAIL){
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
		
		$date = date('Y-m-d');
        $obj->startTrans();
		try{
			$array = array(
				'uuid'=>$uuid,
				'loan_status'=>LoanTransfer::LOAN_STATUS_PAYING,
				'real_pay_date'=>$date
			);
			$obj->params = $array;
			$obj->saveOrUpdate();
			
			$cashFlow = new LoanCashFlow();
			$c = $cashFlow->field(' * ')->where(['loan_transfer_uuid'=>$tranInfo['uuid']])->order(['index'=>'asc'])->select()->toArray();
			if(!isset($c[0]['uuid'])){
				throw new Exception('现金流表数据错误',ErrMsg::RET_CODE_SERVICE_FAIL);
			}
			$amount = 0;
			$need_change = false;
			
			foreach($c as $o){
				//更新本金的实际付款日期
				if($o['cash_flow_type']==1){
					$cashFlow->params = [
					'uuid'=>$o['uuid'],
					'real_repay_date'=>$date
					];
					$cashFlow->saveOrUpdate();
					if($o['repay_date']!=$date){
						$need_change = true;
					}
				}else if($need_change&&$o['cash_flow_type']==3){
					$diff_date = ceil((strtotime($o['repay_date']) - strtotime($date))/86400);
					$real_amount = $diff_date<0?0:round (intval($tranInfo['rate'])* $diff_date / 365);
					$cashFlow->params = [
					'uuid'=>$o['uuid'],
					'real_amount'=>$real_amount,
					'amount'=>$real_amount
					];
					$cashFlow->saveOrUpdate();
					$o['real_amount'] = $real_amount;
				}
			}
			
			$account = new BankAccount();
			$loan_account = $account->getDataById($tranInfo['loan_account_uuid']);
			$collect_account = $account->getDataById($tranInfo['collect_account_uuid']);
			$payInfo = array(
					'trade_type'=>15,
					'order_uuid'=>$tranInfo['transfer_num'],				
					'pay_bank_account'=>$loan_account['bank_account'],
					'collect_bank_account'=>$tranInfo['collect_bank_account'],
					'to_name'=>$tranInfo['collect_account_name'],
					'to_bank_desc'=>$collect_account['bank_name'],
					'to_bank'=>$collect_account['bank_dict_key'],
					'to_city_name'=>$collect_account['city_name'],
					'notice_url'=>'com.jyblife.logic.bg.loan.NoticeResult',
					'jmgUserName'=>$userInfo['username'],
					'jmgPassWord'=>$this->m_request['ukPwd'],
					'amount'=>$tranInfo['amount']
			);
			$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.pay.Order", $payInfo);
			if(empty($ret)||!isset($ret['code'])||$ret['code']!=0){
				throw new Exception("重试接口调用错误",ErrMsg::RET_CODE_SERVICE_FAIL);
			}
			$this->packRet(ErrMsg::RET_CODE_SUCCESS);
            $obj->commit();
		}catch(Exception $e){
            $obj->rollback();
			throw new Exception($e->getMessage()?$e->getMessage():'系统异常',$e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
		}
	}
}