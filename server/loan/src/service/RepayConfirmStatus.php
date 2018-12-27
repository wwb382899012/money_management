<?php

use money\service\BaseService;
use money\model\SysTradeWater;
use money\model\LoanTransfer;
use money\model\Repay;

class RepayConfirmStatus extends BaseService{
	protected $rule = [
		'uuid' => 'require',
		'status' => 'require|integer'
	];
	public function exec()
	{
	    $tran = new LoanTransfer();
		try
		{
			$r = new Repay();
			$repayInfo = $r->loadDatas(['id'=>$this->m_request['uuid']]);
			if(!isset($repayInfo[0]['id'])){
				throw new Exception('回调数据不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
			}
			if($repayInfo[0]['repay_transfer_status']!=Repay::CODE_REPAY_TRANSFER_STATUS_CONFIRMED){
				$this->packRet(ErrMsg::RET_CODE_SUCCESS);
				return;
			}
            $tran->startTrans();
			if($this->m_request['status']==1){
				//成功

				SysTradeWater::confirmStatus($repayInfo[0]['repay_transfer_num'], 1);
				Repay::setSucc($repayInfo[0]['id'],isset($this->m_request['bank_water'])?$this->m_request['bank_water']:null);
				
				//审批流结束
				$ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Approve',
						['flow_code'=>'repay_apply','instance_id'=>$repayInfo[0]['id'],'approve_type'=>'1']);
	
				if(empty($ret)||!isset($ret['code'])||$ret['code']!=0){
					throw new Exception('审批流调用失败',ErrMsg::RET_CODE_SERVICE_FAIL);
				}
			}
			else if($this->m_request['status']==2){

				SysTradeWater::confirmStatus($repayInfo[0]['repay_transfer_num'], 2);
				Repay::setFail($repayInfo[0]['id']);
				//审批流结束
				$ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Approve',
						['flow_code'=>'repay_apply','instance_id'=>$repayInfo[0]['id'],'approve_type'=>'2','info'=>$this->m_request['err_msg']]);
				
				if(empty($ret)||!isset($ret['code'])||$ret['code']!=0){
					throw new Exception('审批流调用失败',ErrMsg::RET_CODE_SERVICE_FAIL);
				}
			}
			
            $tran->commit();
			$this->packRet(ErrMsg::RET_CODE_SUCCESS);
		}catch(Exception $e){
            $tran->rollback();
			throw new Exception('回调失败'.$e->getMessage(),ErrMsg::RET_CODE_SERVICE_FAIL);
		}
	}
}

?>