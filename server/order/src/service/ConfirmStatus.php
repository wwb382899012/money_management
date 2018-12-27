<?php

use money\service\BaseService;
use money\model\EodTradeDb;
use money\model\SysTradeWater;
use money\model\ReportFullTrade;
use money\model\PayOrder;
use money\model\PayTransfer;

class ConfirmStatus extends BaseService{
	protected $rule = [
		'uuid' => 'require',
		'status' => 'require|integer',
		'sessionToken'=>'require'
	];
	
	public function exec()
	{
		$tran = new PayTransfer();
		try
		{
            $tran->startTrans();
			//1、transfer表状态变更
			//2、order表状态变更
			$info = $tran->loadDatas(['uuid'=>$this->m_request['uuid']]);
			if($this->m_request['status']==1){
				//成功
				//$date = date('Y-m-d');
				$tran->params = [
				'uuid'=>$info[0]['uuid'],
				'pay_status'=>PayTransfer::PAY_STATUS_PAID,
				'transfer_status'=>PayTransfer::TRANSFER_STATUS_ARCHIVE,
				'bank_water'=>isset($this->m_request['bank_water'])?$this->m_request['bank_water']:null
				];
				$tran->saveOrUpdate();
	
				$order = new PayOrder();
				$order->params = [
				'uuid'=>$info[0]['pay_order_uuid'],
				'pay_status'=>PayOrder::PAY_STATUS_PAID,
				// 					'order_status'=>PayOrder::ORDER_STATUS_ARCHIVE,
				];
				$order->saveOrUpdate();
				
				SysTradeWater::confirmStatus($info[0]['transfer_num'], 1);
				
				$obj = new ReportFullTrade();
				$obj->saveData(1,$info[0]['transfer_num']);
	
				$ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Approve',
						['flow_code'=>'pay_transfer_pay_type_2_code','instance_id'=>$info[0]['uuid'],'approve_type'=>'1','sessionToken'=>$this->m_request['sessionToken']]);
	
				if(empty($ret)||!isset($ret['code'])||$ret['code']!=0){
					throw new Exception('审批流调用失败',ErrMsg::RET_CODE_SERVICE_FAIL);
				}
				
				$req = [
					'system_flag'=>$info[0]['system_flag'],
					'uuid'=>$info[0]['pay_order_uuid'],
					'trade_type'=>1
				];
				$amqpUtil = new AmqpUtil();
				$ex = $amqpUtil->exchange(ORDER_RESULT_EXCHANGE_NAME);
				$ex->publish(json_encode($req), ORDER_RESULT_LISTENER);
			}
			else if($this->m_request['status']==2){
				$tran->params = [
				'uuid'=>$info[0]['uuid'],
				'pay_status'=>PayTransfer::PAY_STATUS_FAIL,
				'err_msg'=>$this->m_request['err_msg']
				];
				$tran->saveOrUpdate();
				
				SysTradeWater::confirmStatus($info[0]['transfer_num'], 2);
				$r = [
                    'transfer_num'=>$info[0]['transfer_num'],
                    'main_body_uuid'=>$info[0]['pay_main_body_uuid'],
                    'transfer_create_time'=>date('Y-m-d H:i:s'),
                    'limit_date'=>$info[0]['require_pay_datetime'],
                    'opt_uuid'=>$info[0]['uuid'],
                    'trade_type'=>2
				];
				EodTradeDb::dataCreate($r);
				//审批流结束
				$ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Approve',
						['flow_code'=>'pay_transfer_pay_type_2_code','instance_id'=>$info[0]['uuid'],'approve_type'=>'2','info'=>$this->m_request['err_msg'],'sessionToken'=>$this->m_request['sessionToken']]);
	
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