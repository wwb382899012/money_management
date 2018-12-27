<?php
/**
 * 修改还款计划
 */
use money\service\BaseService;
use money\model\Repay;
use money\model\LoanCashFlow;

class RepayCashFlowEdit  extends BaseService {
	protected $rule = [
		'sessionToken' => 'require',
		'repay_id'=>'require'
	];
	
	public function exec() {
		$repayInfo = Repay::getDataById($this->m_request['repay_id']);
		if(empty($repayInfo)){
			throw new Exception('还款id不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		if($repayInfo['repay_transfer_status']!=Repay::CODE_REPAY_TRANSFER_STATUS_WAITING){
			throw new Exception('还款数据处理中，无法修改现金流表',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		
		//$transferInfo = LoanTransfer::getDataById($repayInfo['loan_transfer_uuid']);
		
		$obj = new Repay();
		try{

			$obj->startTrans();
			//添加前清除所有历史数据
			$f = new LoanCashFlow();
			$ret = $f->loadDatas([['loan_transfer_uuid','=',$repayInfo['loan_transfer_uuid']],['status','in',[1,2,4]],['cash_flow_type','!=','1']],'uuid');
			if(is_array($ret)&&count($ret)>0){
				$ids = array_column($ret,'uuid');
				$f->where([['uuid','in',$ids]])->delete();
			}
			$amount = 0;
			if(isset($this->m_request['params']['repayCashDetail'])){
				$cashs = $this->m_request['params']['repayCashDetail'];
				if(is_array($cashs)){
					foreach($cashs as $cash){
						$c = new LoanCashFlow();
			
						$params = array();
						$params['loan_transfer_uuid'] = $repayInfo['loan_transfer_uuid'];
// 						$params['repay_id'] = $this->m_request['instance_id'];
						// 								$params['repay_type'] = $cash['repay_type'];
						$params['index'] = $cash['index'];
						$params['cash_flow_type'] = $cash['cash_flow_type'];
// 						$params['currency'] = $cash['currency'];
						$params['amount'] = $cash['amount'];
						$params['real_amount'] = $cash['real_amount'];
						$params['info'] = $cash['info'];
						$params['status'] = LoanCashFlow::STATUS_WAITING;
						$params['repay_date'] = $cash['repay_date'];
						$c->params = $params;
						$c->saveOrUpdate();
					}
				}
			}
			
			$obj->commit();
			$this->packRet ( ErrMsg::RET_CODE_SUCCESS, null );
		}catch(Exception $e){
			$obj->rollback ();
			// throw new Exception($e->getMessage(),$e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
			throw new Exception($e->getMessage(),$e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
		}
	}
}

?>