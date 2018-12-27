<?php

use money\service\BaseService;
use money\model\EodTradeDb;
use money\model\InnerTransfer;
use money\model\ReportFullTrade;
use money\model\SysTradeWater;
class ConfirmStatus extends BaseService{
	protected $rule = [
		'uuid' => 'require',
		'status' => 'require|integer'
	];
	
	public function exec()
	{
		$tran = new InnerTransfer();
		try
		{
			$tran->startTrans();
			//1、transfer表状态变更
			//2、order表状态变更
			$tran = new InnerTransfer();
			$info = $tran->loadDatas(['uuid'=>$this->m_request['uuid']]);
			if($this->m_request['status']==1){
				//成功
				$tran->params = [
					'uuid'=>$info[0]['uuid'],
					'pay_status'=>InnerTransfer::INNER_STATUS_PAID,
					'transfer_status'=>InnerTransfer::TRANSFER_STATUS_ARCHIVE,
					'transfer_opt_time'=>date('Y-m-d H:i:s'),
					'real_deal_date'=>date('Y-m-d'),
					'bank_water'=>isset($this->m_request['bank_water'])?$this->m_request['bank_water']:null
				];
				$tran->saveOrUpdate();
				SysTradeWater::confirmStatus($info[0]['order_num'], 1);
				
				EodTradeDb::dataOpted($this->m_request['uuid'], 7);
				
				$obj = new ReportFullTrade();
				$obj->saveData(2,$info[0]['order_num']);
				//审批流结束
				$ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Approve',
						['flow_code'=>'inner_transfer_pay_type_2_code','instance_id'=>$info[0]['uuid'],'approve_type'=>'1','info'=>$this->m_request['err_msg']]);
				
				if(empty($ret)||!isset($ret['code'])||$ret['code']!=0){
					throw new Exception('审批流调用失败',ErrMsg::RET_CODE_SERVICE_FAIL);
				}
			}
			else if($this->m_request['status']==2){

				SysTradeWater::confirmStatus($info[0]['order_num'], 2);
				$tran->params = ['uuid'=>$info[0]['uuid'],'pay_status'=>InnerTransfer::INNER_STATUS_FAIL];
				$tran->saveOrUpdate();
				
				$tranInfo = $tran->getDataById($info[0]['uuid']);
				
				//失败重新进入eod表
				$params = [
					'transfer_num'=>$tranInfo['order_num'],
					'main_body_uuid'=>$tranInfo['main_body_uuid'],
					'transfer_create_time'=>date('Y-m-d H:i:s'),
					'limit_date'=>$tranInfo['hope_deal_date'],
					'opt_uuid'=>$tranInfo['uuid'],
					'trade_type'=>7
				];
				EodTradeDb::dataCreate($params);
				//审批流结束
				$ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Approve',
						['flow_code'=>'inner_transfer_pay_type_2_code','instance_id'=>$info[0]['uuid'],'approve_type'=>'2','info'=>$this->m_request['err_msg']]);
				
				if(empty($ret)||!isset($ret['code'])||$ret['code']!=0){
					throw new Exception('审批流调用失败',ErrMsg::RET_CODE_SERVICE_FAIL);
				}
			}
	
			//打款失败逻辑添加
			$tran->commit();
			$this->packRet(ErrMsg::RET_CODE_SUCCESS);
		}catch(Exception $e){
			$tran->rollback();
			throw $e;
// 			throw new Exception('回调失败',ErrMsg::RET_CODE_SERVICE_FAIL);
		}
	}
}

?>