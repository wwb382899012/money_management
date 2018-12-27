<?php

use money\service\BaseService;
use money\model\EodTradeDb;
use money\model\MainBody;
use money\model\ReportFullTrade;
use money\model\SysTradeWater;
use money\model\LoanTransfer;
use money\model\LoanOrder;
use money\model\LoanCashFlow;
use money\model\Repay;

class ConfirmStatus extends BaseService{
	protected $rule = [
		'uuid' => 'require',
		'status' => 'require|integer'
	];
	
	public function exec()
	{
		$tran = new LoanTransfer();
		try
		{
            $tran->startTrans();
			//1、transfer表状态变更
			//2、order表状态变更
			$tran = new LoanTransfer();
			$info = $tran->loadDatas(['uuid'=>$this->m_request['uuid']]);
			
			if($this->m_request['status']==1){
				//成功
				$date = date('Y-m-d');
				$tran->params = ['uuid'=>$info[0]['uuid'],'loan_status'=>LoanTransfer::LOAN_STATUS_PAID,'transfer_status'=>LoanTransfer::TRANSFER_STATUS_ARCHIVE];
				$tran->saveOrUpdate();
	
				$order = new LoanOrder();
				$order->params = ['uuid'=>$info[0]['loan_order_uuid'],'loan_status'=>LoanOrder::LOAN_STATUS_PAID,'order_status'=>LoanOrder::ORDER_STATUS_ARCHIVE];
				$order->saveOrUpdate();
				
				//更新当前现金流表状态
				$cashFlow = new LoanCashFlow();
				$c = $cashFlow->loadLoanCashFlowUuidByTransferUuid($info[0]['uuid']);
				$cashFlow->params = [
					'uuid'=>$c['uuid'],
					'cash_status'=>LoanCashFlow::STATUS_PAID
				];
				$cashFlow->saveOrUpdate();
				
				
				$c = $cashFlow->field(' * ')->where(['loan_transfer_uuid'=>$info[0]['uuid']])->order(['index'=>'asc'])->select()->toArray();
				if(!isset($c[0]['uuid'])){
					throw new Exception('现金流表数据错误',ErrMsg::RET_CODE_SERVICE_FAIL);
				}
				$amount = 0;
				foreach($c as $o){
					if($o['cash_flow_type']!=1){
						$amount+= $o['real_amount'];
					}
				}
				
				//插入还款数据
				$repay = new Repay();
				$mainBody = MainBody::getDataById($info[0]['collect_main_body_uuid']);
				$r_params = [
					'repay_status'=>Repay::CODE_REPAY_STATUS_WATING,
					'repay_transfer_status'=>Repay::CODE_REPAY_TRANSFER_STATUS_WAITING,
					'loan_transfer_uuid'=>$info[0]['uuid'],
					'repay_transfer_num'=>Repay::getOrderNum($mainBody['short_code']),
					'repay_main_body_uuid'=>$info[0]['collect_main_body_uuid'],
					'collect_main_body_uuid'=>$info[0]['loan_main_body_uuid'],
					'repay_account_uuid'=>$info[0]['collect_account_uuid'],
					'collect_account_uuid'=>$info[0]['loan_account_uuid'],
					'index'=>2,
					'currency'=>$info[0]['currency'],
					'amount' => $amount,
					'forecast_date'=>$info[0]['forecast_datetime'],
					'bank_water'=>isset($this->m_request['bank_water'])?$this->m_request['bank_water']:null
				];
				$repay->params = $r_params;
				$id = $repay->saveOrUpdate();
				$tran->params = [
					'uuid'=>$info[0]['uuid'],
					'cur_repay_id'=>$id
				] ;
				$tran->saveOrUpdate();
				SysTradeWater::confirmStatus($info[0]['transfer_num'], 1);
				
				$r = [
					'main_body_uuid'=>$mainBody['uuid'],
					'order_create_time'=>date('Y-m-d H:i:s'),
					'limit_date'=>$info[0]['forecast_datetime'],
					'opt_uuid'=>$id,
					'trade_type'=>6
				];
				EodTradeDb::dataCreate($r);
				
				EodTradeDb::dataOpted( $info[0] ['uuid'], 4);
				
				$obj = new ReportFullTrade();
				$obj->saveData(3,$info[0]['transfer_num']);
				
				//审批流结束
				$ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Approve',
						['flow_code'=>'loan_transfer_pay_type_2_code','instance_id'=>$info[0]['uuid'],'approve_type'=>'1','sessionToken'=>$this->m_request['sessionToken']]);
				
				if(empty($ret)||!isset($ret['code'])||$ret['code']!=0){
					throw new Exception('审批流调用失败',ErrMsg::RET_CODE_SERVICE_FAIL);
				}
			}
			else if($this->m_request['status']==2){
				$tran->params = [
				'uuid'=>$info[0]['uuid'],
				'loan_status'=>LoanTransfer::LOAN_STATUS_FAIL,
				'err_msg'=>$this->m_request['err_msg']
				];
				$tran->saveOrUpdate();

				SysTradeWater::confirmStatus($info[0]['transfer_num'], 2);
				$params = [
				'transfer_num'=>$info[0]['transfer_num'],
				'main_body_uuid'=>$info[0]['loan_main_body_uuid'],
				'transfer_create_time'=>date('Y-m-d H:i:s'),
				'limit_date'=>$info[0]['loan_datetime'],
				'opt_uuid'=>$info[0]['uuid'],
				'trade_type'=>4
				];
				EodTradeDb::dataCreate($params);
				//审批流结束
				$ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Approve',
						['flow_code'=>'loan_transfer_pay_type_2_code','instance_id'=>$info[0]['uuid'],'approve_type'=>'2','info'=>$this->m_request['err_msg']]);
				
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