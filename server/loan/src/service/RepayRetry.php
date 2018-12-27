<?php
use money\service\BaseService;
use money\model\LoanTransfer;
use money\model\RepayCashFlow;
use money\model\Repay;
use money\model\SysTradeWater;

class RepayRetry extends BaseService{
	protected $rule = [
		'uuid' => 'require'
	];
	
	//重试
	public function exec(){
// 		$uuid = $this->m_request['uuid'];
// 		$obj = new LoanTransfer();
// 		$tranInfo = $obj->getDataById($uuid);
// 		if(!is_array($tranInfo)||count($tranInfo)==0){
// 			throw new Exception('数据不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
// 		}
		
// 		if($tranInfo['repay_status']!=LoanTransfer::REPAY_STATUS_FAIL){
// 			throw new Exception('打款中或者打款成功无法重复打款',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
// 		}
		
// 		if(!isset($this->m_request['ukPwd'])){
// 			throw new Exception('加密狗密码不能为空',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
// 		}
		
// 		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.layer.SessionGet", array('sessionToken'=>$this->m_request['sessionToken']));
// 		if(!isset($ret['code'])||$ret['code']!=0||empty($ret['data'])){
// 			throw new Exception('无法获取用户信息',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
// 		}
// 		$userInfo = $ret['data'];
		
// 		$obj->startTrans();
// 		try{
// 			$array = array(
// 				'uuid'=>$uuid,
// 				'repay_status'=>LoanTransfer::LOAN_STATUS_PAYING
// 			);
// 			$obj->params = $array;
// 			$obj->saveOrUpdate();
			
// 			$repayInfo = Repay::getDataById($tranInfo['cur_repay_id']);
// 			$c = new RepayCashFlow();
// 			$cashs = $c->loadDatas([['repay_id','=',$repayInfo['id']]],'uuid');
			
// 			foreach($cashs as $r){
// 				$c->params = [
// 					'uuid'=>$r['uuid'],
// 					'status'=>RepayCashFlow::STATUS_PAYING
// 				];
// 				$c->saveOrUpdate();
// 			}
// 			$w = new SysTradeWater();
// 			$waters = $w->loadDatas(['order_uuid'=>$repayInfo['repay_transfer_num']]);
// 			foreach($waters as $water){
// 				$w->params = [
// 					'uuid'=>$water['uuid'],
// 					'status'=>SysTradeWater::STATUS_PAYING
// 				];
// 				$w->saveOrUpdate();
// 			}
			
// 			Repay::pay($this->m_request['uuid'] ,$this->m_request['ukPwd'] ,$this->m_request['sessionToken'] , $repayInfo);
// 			$obj->commit();
// 		}catch(Exception $e){
// 			$obj->rollback();
// 			throw new Exception($e->getMessage()?$e->getMessage():'系统异常',$e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
// 		}
	}
}

?>