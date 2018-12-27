<?php

use money\service\BaseService;
use money\model\InnerTransfer;
use money\model\EodTradeDb;
use money\model\ReportFullTrade;
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

		$order_uuid = $this->m_request['order_uuid'];
		
		$tran = new InnerTransfer();
		$info = $tran->loadDatas(['order_num'=>$this->m_request['order_uuid']]);
		if(!is_array($info)||count($info)==0){
			throw new Exception('回调数据不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		
		try
		{	
			//1、transfer表状态变更
			//2、order表状态变更
            $tran->startTrans();
			if($this->m_request['status']==3){
				//成功
				$tran->params = [
					'uuid'=>$info[0]['uuid'],
					'pay_status'=>InnerTransfer::INNER_STATUS_PAID,
					'transfer_status'=>InnerTransfer::TRANSFER_STATUS_ARCHIVE,
					'transfer_opt_time'=>date('Y-m-d H:i:s'),
					'real_deal_date'=>date('Y-m-d'),
					'bank_water'=>isset($this->m_request['serialId'])?$this->m_request['serialId']:null
				];
				$tran->saveOrUpdate();
				
				$obj = new ReportFullTrade();
				$obj->saveData(2,$info[0]['order_num']);
				
				$ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Approve',
						['flow_code'=>'inner_transfer_pay_type_2_code','instance_id'=>$info[0]['uuid'],'approve_type'=>'1']);
				
				if(empty($ret)||!isset($ret['code'])||$ret['code']!=0){
					throw new Exception('审批流调用失败',ErrMsg::RET_CODE_SERVICE_FAIL);
				}
			}
			else if($this->m_request['status']==4){
				//审批流结束 	
				$ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Approve',
						['flow_code'=>'inner_transfer_pay_type_2_code','instance_id'=>$info[0]['uuid'],'approve_type'=>'2','info'=>$this->m_request['err_msg']]);
				
				if(empty($ret)||!isset($ret['code'])||$ret['code']!=0){
					throw new Exception('审批流调用失败',ErrMsg::RET_CODE_SERVICE_FAIL);
				}
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
				
			}else if($this->m_request['status']==5){
				$tran->params = [
					'uuid'=>$info[0]['uuid'],
					'pay_status'=>InnerTransfer::INNER_STATUS_UNCONFIRM
				];
				$tran->saveOrUpdate();
				
				$instance = new SysAuditFlowInstance();
				$i = $instance->loadDatas(['instance_id'=>$info[0]['uuid'],'flow_uuid'=>14]);
				if(!is_array($i)||count($i)==0){
					throw new Exception('审批流不存在',ErrMsg::RET_CODE_SERVICE_FAIL);
				}
				$user_id = $i[0]['create_user_id'];
				$u = SysUser::getUserInfoByIds([$user_id]);
				$main_body = MainBody::getDataById($info[0]['main_body_uuid']);
				$array = [
					'business_type'=>'inner',
					'business_son_type'=>'confirm',
					'content'=>$u['name'].'您好，内部调拨交易（'.$info[0]['order_num'].'）银企结果待手动确认，收款方为'.$main_body['full_name'].'，付款金额为'.round($info[0]['amount']/100,2).'，请登录系统进行处理',
					'business_uuid'=>$info[0]['uuid'],
					'send_datetime'=>date('Y-m-d H:i:s'),
					'create_time'=>date('Y-m-d H:i:s'),
					'deal_user_id'=>$u[0]['user_id']
				];
				$mail = $array;
				$mail['title'] = '内部调拨交易待确认';
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
			throw new Exception('回调失败|'.$e->getMessage(),ErrMsg::RET_CODE_SERVICE_FAIL);
		}
	}
}

?>