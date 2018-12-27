<?php

use money\service\BaseService;
use money\model\EodTradeDb;
use money\model\ReportFullTrade;
use money\model\PayTransfer;
use money\model\PayOrder;
use money\base\AmqpUtil;
use money\model\SysAuditFlowInstance;
use money\model\SysUser;
use money\model\MainBody;
use money\model\SysWebNews;
use money\model\SysMailNews;
use money\model\SysTradeWater;

class NoticeResult extends BaseService{
	
	protected $rule = [
		'order_uuid' => 'require',
		'status' => 'require|integer'
	];
	
	public function exec()
	{
		$tran = new PayTransfer();
		try
		{
            $tran->startTrans();
			//1、transfer表状态变更
			//2、order表状态变更
			$info = $tran->loadDatas(['transfer_num'=>$this->m_request['order_uuid']]);
			if($this->m_request['status']==3){
				//成功
				//$date = date('Y-m-d');
				$tran->params = [
					'uuid'=>$info[0]['uuid'],
					'pay_status'=>PayTransfer::PAY_STATUS_PAID,
					'transfer_status'=>PayTransfer::TRANSFER_STATUS_ARCHIVE,
					'bank_water'=>isset($this->m_request['serialId'])?$this->m_request['serialId']:null				
				];
				$tran->saveOrUpdate();
				
				$order = new PayOrder();
				$order->params = [
					'uuid'=>$info[0]['pay_order_uuid'],
					'pay_status'=>PayOrder::PAY_STATUS_PAID,
// 					'order_status'=>PayOrder::ORDER_STATUS_ARCHIVE,
				];
				$order->saveOrUpdate();
				
				$ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Approve',
						['flow_code'=>'pay_transfer_pay_type_2_code','instance_id'=>$info[0]['uuid'],'approve_type'=>'1']);
				
				if(empty($ret)||!isset($ret['code'])||$ret['code']!=0){
					throw new Exception('审批流调用失败|msg:'.json_encode($ret),ErrMsg::RET_CODE_SERVICE_FAIL);
				}
				$req = [
					'system_flag'=>$info[0]['system_flag'],
					'uuid'=>$info[0]['pay_order_uuid'],
					'trade_type'=>1
				];
				$amqpUtil = new AmqpUtil();
				$ex = $amqpUtil->exchange(ORDER_RESULT_EXCHANGE_NAME);
				$ex->publish(json_encode($req), ORDER_RESULT_LISTENER);
				
				$obj = new ReportFullTrade();
				$obj->saveData(1,$info[0]['transfer_num']);
			}
			else if($this->m_request['status']==4){
				//审批流结束
				$ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Approve',
						['flow_code'=>'pay_transfer_pay_type_2_code','instance_id'=>$info[0]['uuid'],'approve_type'=>'2','info'=>$this->m_request['err_msg']]);
				
				if(empty($ret)||!isset($ret['code'])||$ret['code']!=0){
					throw new Exception('审批流调用失败|msg:'.$ret,ErrMsg::RET_CODE_SERVICE_FAIL);
				}
				$tran->params = [
					'uuid'=>$info[0]['uuid'],
					'pay_status'=>PayTransfer::PAY_STATUS_FAIL,
					'err_msg'=>$this->m_request['err_msg']
				];
				$tran->saveOrUpdate();
				
				$r = [
					'transfer_num'=>$info[0]['transfer_num'],
					'main_body_uuid'=>$info[0]['pay_main_body_uuid'],
					'transfer_create_time'=>date('Y-m-d H:i:s'),
					'limit_date'=>$info[0]['require_pay_datetime'],
					'opt_uuid'=>$info[0]['uuid'],
					'trade_type'=>2
				];
				EodTradeDb::dataCreate($r);
			}else if($this->m_request['status']==5){

				$tran->params = [
				'uuid'=>$info[0]['uuid'],
				'pay_status'=>PayTransfer::PAY_STATUS_UNCONFIRM,
				'err_msg'=>$this->m_request['err_msg']
				];
				$tran->saveOrUpdate();
				
				$instance = new SysAuditFlowInstance();
				$i = $instance->loadDatas(['instance_id'=>$info[0]['uuid'],'flow_uuid'=>7]);
				if(!is_array($i)||count($i)==0){
					throw new Exception('审批流不存在',ErrMsg::RET_CODE_SERVICE_FAIL);
				}
				$user_id = $i[0]['create_user_id'];
				$u = SysUser::getUserInfoByIds([$user_id]);
				$main_body = MainBody::getDataById($info[0]['pay_main_body_uuid']);
				$array = [
					'business_type'=>'order',
					'business_son_type'=>'transfer.confirm',
					'content'=>$u['name'].'您好，付款调拨交易（'.$info[0]['transfer_num'].'）银企结果待手动确认，收款方为'.$main_body['full_name'].'，付款金额为'.round($info[0]['amount']/100,2).'，请登录系统进行处理',
					'business_uuid'=>$info[0]['uuid'],
					'send_datetime'=>date('Y-m-d H:i:s'),
					'create_time'=>date('Y-m-d H:i:s'),
					'deal_user_id'=>$u[0]['user_id']
				];
				$mail = $array;
				$mail['title'] = '付款调拨交易待确认';
				$mail['deal_user_name'] = $u[0]['name'];
				$mail['email_address'] = $u[0]['email'];
				$webDb = new SysWebNews();
				$mailDb = new SysMailNews();
				$webDb->addMsg($array);
				$mailDb->addMsg($mail);
			}
			
			//打款失败逻辑添加
            $tran->commit();
			$this->packRet(ErrMsg::RET_CODE_SUCCESS);
		}catch(Exception $e){
            $tran->rollback();
            throw $e;
// 			throw new Exception('回调失败'.$e->getMessage(),ErrMsg::RET_CODE_SERVICE_FAIL);
		}
	}
}
