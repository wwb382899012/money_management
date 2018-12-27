<?php
/**
*	付款调拨审批流结果监听
*	@author sun
*	@since 2018-03-11
*/
use money\service\BaseService;
use money\model\SysTradeWater;
use money\model\PayTransfer;
use money\model\PayOrder;
use money\model\SysUser;
use money\model\EodTradeDb;
use money\base\AreaUtil;
use money\model\ReportFullTrade;
use money\model\BankAccount;
use money\model\SysAuditFlowInstance;
use money\model\MainBody;
use money\model\SysWebNews;
use money\model\SysMailNews;
use money\logic\CommonLogic;

class PayTransferFlowListener extends BaseService
{
    protected $rule = [
        //'sessionToken' => 'require',
        'instance_id' => 'require',
        'flow_code' => 'require',
        'node_code' => 'require',
        'node_status' => 'require|integer',
    ];

	public function exec()
	{
		if($this->m_request['flow_code']=='pay_transfer_pay_type_1_code'){
			switch($this->m_request['node_code'])
			{
				case "Pay_transfer_begin_wy":
					//资金专员
					$params = array();
					$params['uuid'] = $this->m_request['instance_id'];
					
					if(!isset($this->m_request['params']['real_pay_type'])){
						throw new \Exception('实付类型不能为空',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
					}
					$params['real_pay_type'] =  $this->m_request['params']['real_pay_type'];
					if(isset($this->m_request['params']['annex_uuids'])){
						$params['annex_uuids'] =  $this->m_request['params']['annex_uuids'];
					}
					
					if(!isset($this->m_request['params']['pay_account_uuid'])||empty($this->m_request['params']['pay_account_uuid'])){
						throw new \Exception('打款账户不能为空',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
					}
					
					$oBankAmount = new BankAccount();
					$params['pay_account_uuid'] = $this->m_request['params']['pay_account_uuid'];
					$pay_bank_account = $oBankAmount->getDataById($params['pay_account_uuid']);
					$params['pay_bank_account'] = $pay_bank_account['bank_account'];
					$params['pay_account_name'] = $pay_bank_account['account_name'];
					$params['pay_bank_name'] = $pay_bank_account['bank_name'];
					$params['transfer_status'] = PayTransfer::TRANSFER_STATUS_OPTED;
					$params['require_pay_datetime'] = $this->m_request['params']['require_pay_datetime'];
					
					$tranInfo = PayTransfer::getDataById ( $this->m_request ['instance_id'] );
					if (isset ( $this->m_request ['params'] ['collect_account_uuid'] ) && ! empty ( $this->m_request ['params'] ['collect_account_uuid'] )) {
						$collect_account_uuid =  $this->m_request ['params'] ['collect_account_uuid'];
					}else if(isset($tranInfo['collect_account_uuid'])){
						$collect_account_uuid = $tranInfo['collect_account_uuid'];
					}
					if(isset($collect_account_uuid)&&!empty($collect_account_uuid)){
						$collect_bank_account = $oBankAmount->getDataById ( $collect_account_uuid );
						$params['collect_account_name'] = $collect_bank_account['account_name'];
						$params['collect_account_uuid'] = $collect_bank_account ['uuid'];
						$params['collect_bank_account'] = $collect_bank_account['bank_account'];
						$params['collect_bank_desc'] = $collect_bank_account['bank_name'];
						$params['collect_bank_name'] = $collect_bank_account['bank_name'];
						$params['collect_bank'] = $collect_bank_account['bank_dict_key'];
						$params['collect_city_name'] = $collect_bank_account['province'].$collect_bank_account['city_name'];
						$params['collect_city'] = $collect_bank_account['city'];
						$params['collect_bank_link_code'] = $collect_bank_account['bank_link_code'];
					}
					
					$tran = new PayTransfer();
					$tran->params = $params;
					$tran->saveOrUpdate();
					
					EodTradeDb::dataUpdate($this->m_request['instance_id'], 2
						, ['limit_date'=>$this->m_request['params']['require_pay_datetime'],'transfer_create_time'=>date('Y-m-d H:i:s')]);
					break;
				case "Pay_transfer_approve_wy_1":
					//权签人
					
					$tran = new PayTransfer();
					if($this->m_request['node_status']==3)
					{
						//审批驳回
						$params = array();
						$params['uuid'] = $this->m_request['instance_id'];
						$params['transfer_status'] = PayTransfer::TRANSFER_STATUS_WAITING;
						$tran->params = $params;
						$tran->saveOrUpdate();
						EodTradeDb::dataUpdate($this->m_request['instance_id'], 2, ['transfer_create_time'=>null]);
						
					}else if($this->m_request['node_status']==2){
						//审批通过

						$tranInfo = PayTransfer::getDataById($this->m_request['instance_id']);

						try{

							$tran->startTrans();
							if($tranInfo['transfer_pay_type']!=5){
								$params = array();
								$date = date('Y-m-d');
								$params['uuid'] = $this->m_request['instance_id'];
								$params['transfer_status'] = PayTransfer::TRANSFER_STATUS_WAIT_TICKET_BACK;
								$params['pay_status'] = PayTransfer::PAY_STATUS_PAID;
								$params['need_ticket_back'] = 1;
								$params['real_pay_date'] = $date;
								$tran->params = $params;
								$tran->saveOrUpdate();
									
								$order = new PayOrder();
								$params = array();
								$params['uuid'] = $tranInfo['pay_order_uuid'];
								$params['real_pay_date'] = $date;
								$params['optor']=$this->m_request['optor'];
								$params['opt_msg'] =isset($this->m_request['msg'])?$this->m_request['msg']:null;
								$order->params = $params;
								$order->saveOrUpdate();
							}else{
								$params = array();
								$date = date('Y-m-d');
								$params['uuid'] = $this->m_request['instance_id'];
								$params['transfer_status'] = PayTransfer::TRANSFER_STATUS_ARCHIVE;
								$params['pay_status'] = PayTransfer::PAY_STATUS_PAID;
								$params['need_ticket_back'] = 1;
								$params['real_pay_date'] = $date;
								$tran->params = $params;
								$tran->saveOrUpdate();
								
								$order = new PayOrder();
								$params = array();
								$params['uuid'] = $tranInfo['pay_order_uuid'];
								$params['pay_status'] = PayOrder::PAY_STATUS_PAID;
								$params['real_pay_date'] = $date;
								$params['optor']=$this->m_request['optor'];
								$params['opt_msg'] =isset($this->m_request['msg'])?$this->m_request['msg']:null;
								$order->params = $params;
								$order->saveOrUpdate();
								
								//现金流表写入
								$oBankAmount = new BankAccount();
								$pay_bank_account = $oBankAmount->getDataById($tranInfo['pay_account_uuid']);
								
								$obj = new SysTradeWater();
								$params = array();
								//2 保存数据，状态为等待提交
								$params['trade_type'] = $tranInfo['transfer_pay_type'];
								$params['order_uuid'] = $tranInfo['transfer_num'];
								
								//$params['trnuId'] = SysTradeWater::getTrnuId();
								$params['pay_account_uuid'] = $pay_bank_account['uuid'];
								$params['pay_bank_key'] = $pay_bank_account['bank_dict_key'];
								$params['pay_bank_account'] = $pay_bank_account['bank_account'];
								$params['collect_bank_account'] = $tranInfo['collect_bank_account'];
								$params['to_name'] = $tranInfo['collect_account_name'];
								$params['to_bank_desc'] = $tranInfo['collect_bank_account'];
								$params['to_bank'] = $tranInfo['collect_bank'];
								$params['to_city_name'] = $tranInfo['collect_city_name'];
								
								$params['amount'] = $tranInfo['amount'];
								$params['currency'] = 'CYN';
								$params['is_effective'] = SysTradeWater::STATUS_EFFECT;
								$params['status'] = SysTradeWater::STATUS_SUCCESS;
								$uuid = $obj->addWater($params);
								
								$tran->params = [
									'uuid'=>$tranInfo['uuid'],
									'water_uuid'=>$uuid
								];
								$tran->saveOrUpdate();
								if($tranInfo['transfer_pay_type']==5){
									$req = [
										'system_flag'=>$tranInfo['system_flag'],
										'uuid'=>$tranInfo['pay_order_uuid'],
										'trade_type'=>1
									];
									$amqpUtil = new AmqpUtil();
									$ex = $amqpUtil->exchange(ORDER_RESULT_EXCHANGE_NAME);
									$ex->publish(json_encode($req), ORDER_RESULT_LISTENER);
									EodTradeDb::dataOpted( $tranInfo ['uuid'], 2);
									$obj = new ReportFullTrade();
									$obj->saveData(1,$tranInfo['transfer_num']);
								}
							}
							$tran->commit();
						}catch(Exception $e){
							$tran->rollback();
							throw new Exception($e->getMessage(),$e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
						}
					}else if($this->m_request['node_status']==4){
						//审批拒绝
						//更新指令、调拨的状态为拒绝，打款状态为打款拒绝
						try{
							$tran->startTrans();
							$tran->params = [];
							$tran->params['uuid'] = $this->m_request['instance_id'];
							$tran->params['transfer_status'] = PayTransfer::TRANSFER_STATUS_REFUSE;
							$tran->params['pay_status'] = PayTransfer::PAY_STATUS_FAIL;
							$tran->saveOrUpdate();
							
							$tranInfo = PayTransfer::getDataById($this->m_request['instance_id']);
							$order = new PayOrder();
							$order->params = [
								'uuid'=>$tranInfo['pay_order_uuid'],
								'pay_status'=>PayOrder::PAY_STATUS_FAIL,
								'order_status'=>PayOrder::ORDER_STATUS_REFUSE,
								'optor'=>$this->m_request['optor'],
								'opt_msg'=>isset($this->m_request['msg'])?$this->m_request['msg']:null
							];
							$order->saveOrUpdate();

							EodTradeDb::dataOpted( $tranInfo ['uuid'], 2);
							
							$req = [
								'system_flag'=>$tranInfo['system_flag'],
								'uuid'=>$tranInfo['pay_order_uuid'],
								'trade_type'=>1
							];
							$amqpUtil = new AmqpUtil();
							$ex = $amqpUtil->exchange(ORDER_RESULT_EXCHANGE_NAME);
							$ex->publish(json_encode($req), ORDER_RESULT_LISTENER);
							
// 							$obj = new ReportFullTrade();
// 							$obj->saveData(1,$tranInfo['transfer_num']);
							$tran->commit();
						}catch(Exception $e){
// 							throw $e;
							$tran->rollback();
							throw new Exception('系统异常',$e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
						}
					}
					break;
				case "Pay_transfer_approve_wy_2":
					//付款
					$params = array();
					$params['uuid'] = $this->m_request['instance_id'];
					$params['bank_water'] = isset($this->m_request['params']['bank_water']) ? $this->m_request['params']['bank_water'] : '';
					$params['bank_img_file_uuid'] = isset($this->m_request['params']['bank_img_file_uuid']) ? $this->m_request['params']['bank_img_file_uuid'] : '';
					$tran = new PayTransfer();
					$tran->params = $params;
					$tran->startTrans();
					try{
						//上传回单不考虑失败情况，没有驳回或者拒绝
						if($this->m_request['node_status']==2){
							$tran->params['pay_status'] = PayTransfer::PAY_STATUS_PAID;
							$tran->params['need_ticket_back'] = 0;
							$tran->params['transfer_status'] = PayTransfer::TRANSFER_STATUS_ARCHIVE;
							$tran->saveOrUpdate();
							
							$tranInfo = PayTransfer::getDataById($this->m_request['instance_id']);
							if($tranInfo['transfer_pay_type']!=5){
								$order = new PayOrder();
								$params = array();
								$params['uuid'] = $tranInfo['pay_order_uuid'];
								$params['pay_status'] = PayOrder::PAY_STATUS_PAID;
								$order->params = $params;
								$order->saveOrUpdate();
								
								$params = array();
								//$date = date('Y-m-d');
								$params['uuid'] = $this->m_request['instance_id'];
								$params['transfer_status'] = PayTransfer::TRANSFER_STATUS_ARCHIVE;
								$params['pay_status'] = PayTransfer::PAY_STATUS_PAID;
								$tran->params = $params;
								$tran->saveOrUpdate();
								
								//现金流表写入
								$oBankAmount = new BankAccount();
								$pay_bank_account = $oBankAmount->getDataById($tranInfo['pay_account_uuid']);
									
								$obj = new SysTradeWater();
								$params = array();
								//2 保存数据，状态为等待提交
								$params['trade_type'] = $tranInfo['transfer_pay_type'];
								$params['order_uuid'] = $tranInfo['transfer_num'];
									
								//$params['trnuId'] = SysTradeWater::getTrnuId();
								$params['pay_account_uuid'] = $pay_bank_account['uuid'];
								$params['pay_bank_key'] = $pay_bank_account['bank_dict_key'];
								$params['pay_bank_account'] = $pay_bank_account['bank_account'];
								$params['collect_bank_account'] = $tranInfo['collect_bank_account'];
								$params['to_name'] = $tranInfo['collect_account_name'];
								$params['to_bank_desc'] = $tranInfo['collect_bank_account'];
								$params['to_bank'] = $tranInfo['collect_bank'];
								$params['to_city_name'] = $tranInfo['collect_city_name'];
									
								$params['amount'] = $tranInfo['amount'];
								$params['currency'] = 'CYN';
								$params['is_effective'] = SysTradeWater::STATUS_EFFECT;
								$params['status'] = SysTradeWater::STATUS_SUCCESS;
								$uuid = $obj->addWater($params);
									
								$tran->params = [
								'uuid'=>$tranInfo['uuid'],
								'water_uuid'=>$uuid
								];
								$tran->saveOrUpdate();
								$req = [
								'system_flag'=>$tranInfo['system_flag'],
								'uuid'=>$tranInfo['pay_order_uuid'],
								'trade_type'=>1
								];
								$amqpUtil = new AmqpUtil();
								$ex = $amqpUtil->exchange(ORDER_RESULT_EXCHANGE_NAME);
								$ex->publish(json_encode($req), ORDER_RESULT_LISTENER);
								EodTradeDb::dataOpted( $tranInfo ['uuid'], 2);
								$obj = new ReportFullTrade();
								$obj->saveData(1,$tranInfo['transfer_num']);
							}else{
								//现金流表更新状态
								$obj = new SysTradeWater();
								$params = [
									'uuid'=>$tranInfo['water_uuid'],
									'status'=>3
								];
								$obj->params = $params;
								$obj->saveOrUpdate();
								$r = new ReportFullTrade();
								$reportInfo = $r->loadDatas(['trade_uuid'=>$tranInfo['uuid']]);
								if(is_array($reportInfo)&&count($reportInfo)>0){
									$params = [
										'uuid'=>$reportInfo[0]['uuid'],
										'bank_water_no'=>$tranInfo['bank_water']
									];
									$r->params = $params;
									$r->saveOrUpdate();
								}
							}
							
							$tran->commit();
						}
					}catch(Exception $e){
						$tran->rollback();
						throw new Exception('系统异常',$e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
					}
					break;
				default:
					break;
			}
			
		}else if ($this->m_request['flow_code']=='pay_transfer_pay_type_2_code'){
			//付款银企
			switch($this->m_request['node_code'])
			{
				case "Pay_transfer_begin_yq":
					//资金专员
					$params = array();
					$params['uuid'] = $this->m_request['instance_id'];
					
					if(!isset($this->m_request['params']['real_pay_type'])){
						throw new Exception('实付类型不能为空',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
					}
					$params['real_pay_type'] =  $this->m_request['params']['real_pay_type'];
					$params['pay_remark'] = $this->m_request['params']['pay_remark'];
					if(isset($this->m_request['params']['annex_uuids'])){
						$params['annex_uuids'] =  $this->m_request['params']['annex_uuids'];
					}
					
					if(!isset($this->m_request['params']['pay_account_uuid'])||empty($this->m_request['params']['pay_account_uuid'])){
						throw new Exception('打款账户不能为空',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
					}
					
					$oBankAmount = new BankAccount();
					$params['pay_account_uuid'] = $this->m_request['params']['pay_account_uuid'];
					$pay_bank_account = $oBankAmount->getDataById($params['pay_account_uuid']);
					$params['pay_bank_account'] = $pay_bank_account['bank_account'];
					$params['pay_account_name'] = $pay_bank_account['account_name'];
					$params['pay_bank_name'] = $pay_bank_account['bank_name'];
					$params['transfer_status'] = PayTransfer::TRANSFER_STATUS_OPTED;
					$params['require_pay_datetime'] = $this->m_request['params']['require_pay_datetime'];
					
					$tranInfo = PayTransfer::getDataById ( $this->m_request ['instance_id'] );
					if (isset ( $this->m_request ['params'] ['collect_account_uuid'] ) && ! empty ( $this->m_request ['params'] ['collect_account_uuid'] )) {
						$collect_account_uuid =  $this->m_request ['params'] ['collect_account_uuid'];
					}else if(isset($tranInfo['collect_account_uuid'])&&!empty($tranInfo['collect_account_uuid'])){
						$collect_account_uuid = $tranInfo['collect_account_uuid'];
					}
                    
					if(isset($collect_account_uuid)){
						$collect_bank_account = $oBankAmount->getDataById ( $collect_account_uuid );
						$params['collect_account_name'] = $collect_bank_account['account_name'];
						$params['collect_account_uuid'] = $collect_bank_account ['uuid'];
						$params['collect_bank_account'] = $collect_bank_account['bank_account'];
						$params['collect_bank_desc'] = $collect_bank_account['bank_name'];
						$params['collect_bank_name'] = $collect_bank_account['bank_name'];
						$params['collect_bank'] = $collect_bank_account['bank_dict_key'];
						$params['collect_city_name'] = $collect_bank_account['province'].$collect_bank_account['city_name'];
						$params['collect_city'] = $collect_bank_account['city'];
						$params['collect_bank_link_code'] = $collect_bank_account['bank_link_code'];
						$tranInfo['collect_bank_link_code'] = $params['collect_bank_link_code'];
					}
					if(in_array($pay_bank_account['bank_dict_key'],[4,5])){
						//平安、农行收款账号行号不能为空
						if(empty($tranInfo['collect_bank_link_code'])){
							throw new Exception ( '付款银行平安或农行，收款账号行号不能为空，请维护账号后再提交', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR );
						}
					}
					$tran = new PayTransfer();
					$tran->params = $params;
					$tran->saveOrUpdate();
					
					
					EodTradeDb::dataUpdate($this->m_request['instance_id'], 2, ['limit_date'=>$this->m_request['params']['require_pay_datetime'],'transfer_create_time'=>date('Y-m-d H:i:s')]);
					break;
				case "Pay_transfer_approve_yq_1":
					//权签人
					$params = array();
					$params['uuid'] = $this->m_request['instance_id'];
					$tran = new PayTransfer();
					if($this->m_request['node_status']==3)
					{
						//审批驳回
						$params = array();
						$params['uuid'] = $this->m_request['instance_id'];
						$params['transfer_status'] = PayTransfer::TRANSFER_STATUS_WAITING;
						$tran->params = $params;
						$tran->saveOrUpdate();
						
						EodTradeDb::dataUpdate($this->m_request['instance_id'], 2, ['transfer_create_time'=>null]);
					}else if($this->m_request['node_status']==4){
					//审批拒绝
						//更新指令、调拨的状态为拒绝，打款状态为打款拒绝
						
						try{
							$tran->startTrans();
							$tran->params = $params;
							$tran->params['transfer_status'] = PayTransfer::TRANSFER_STATUS_REFUSE;
							$tran->params['pay_status'] = PayTransfer::PAY_STATUS_FAIL;
							$tran->saveOrUpdate();
							
							$tranInfo = PayTransfer::getDataById($this->m_request['instance_id']);
							$order = new PayOrder();
							$order->params = [
								'uuid'=>$tranInfo['pay_order_uuid'],
								'pay_status'=>PayOrder::PAY_STATUS_FAIL,
								'order_status'=>PayOrder::ORDER_STATUS_REFUSE,
								'optor'=>$this->m_request['optor'],
								'opt_msg'=>isset($this->m_request['msg'])?$this->m_request['msg']:null
							];
							$order->saveOrUpdate();

							EodTradeDb::dataOpted( $this->m_request['instance_id'], 2);
							
							$req = [
								'system_flag'=>$tranInfo['system_flag'],
								'uuid'=>$tranInfo['pay_order_uuid'],
								'trade_type'=>1
							];
							$amqpUtil = new AmqpUtil();
							$ex = $amqpUtil->exchange(ORDER_RESULT_EXCHANGE_NAME);
							$ex->publish(json_encode($req), ORDER_RESULT_LISTENER);
							
							$tran->commit();
						}catch(Exception $e){
							$tran->rollback();
							throw new Exception('系统异常',$e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
						}
						
					}
					else
					{	
						$this->transferOpt($this->m_request);
					}
					break;
				case "Pay_transfer_approve_yq_2":
					//驳回和拒绝都要调用流程拒绝接口。只是数据修改状态不同。
// 					if($this->m_request['node_status']==3){
// // 						$tran = new PayTransfer();
// // 						$tran->params['uuid'] = $this->m_request['instance_id']; 
// // 						$tran->params['transfer_status'] = PayTransfer::PAY_STATUS_FAIL;
// // 						$tran->params['pay_status'] = PayTransfer::PAY_STATUS_UNPAID;
// // 						$tran->saveOrUpdate();
// 					}else if($this->m_request['node_status']==4){
//                         $tran = new PayTransfer();
// 						$tran->params['uuid'] = $this->m_request['instance_id'];
// 						$tran->params['transfer_status'] = PayTransfer::TRANSFER_STATUS_REFUSE;
// 						$tran->params['pay_status'] = PayTransfer::PAY_STATUS_FAIL;
// 						$tran->saveOrUpdate();
							
// 						$tranInfo = PayTransfer::getDataById($this->m_request['instance_id']);
// 						$order = new PayOrder();
// 						$order->params = [
// 							'uuid'=>$tranInfo['pay_order_uuid'],
// 							'pay_status'=>PayOrder::PAY_STATUS_FAIL,
// 							'order_status'=>PayOrder::ORDER_STATUS_REFUSE,
// 							'optor'=>$this->m_request['optor'],
// 							'opt_msg'=>isset($this->m_request['msg'])?$this->m_request['msg']:null
// 						];
// 						$order->saveOrUpdate();
// 					}
					break;
				default:
					break;
			}
		}
		$this->msgOpt();
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, null);
	}

	public function transferOpt($params)
	{
		$uuid = $params['instance_id'];
		$tran = new PayTransfer();

		if(!isset($params['params']['jmgPassWord'])){
			throw new \Exception('u盾密码不能为空',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}
		
		try
		{
			$tran->startTrans();
			$tran = new PayTransfer();
			$tran->params['uuid'] = $uuid;
			$tran->params['transfer_status'] = PayTransfer::TRANSFER_STATUS_COMFIRMED;
			$tran->params['pay_status'] = PayTransfer::PAY_STATUS_PAYING;
			$tran->saveOrUpdate();
			
			$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.layer.SessionGet", array('sessionToken'=>$params['sessionToken']));
			if(!isset($ret['code'])||$ret['code']!=0||empty($ret['data'])){
				throw new Exception('无法获取用户信息');
			}
			$userInfo = $ret['data'];
			
			$tranInfo = $tran->getDataById($uuid);
// 			$order = new PayOrder();
// 			$order->params = [
// 				'uuid'=>$tranInfo['pay_order_uuid'],
// 				'pay_status'=>PayOrder::PAY_STATUS_FAIL,
// 				'order_status'=>PayOrder::ORDER_STATUS_REFUSE,
// 				'optor'=>$this->m_request['optor'],
// 				'opt_msg'=>isset($this->m_request['msg'])?$this->m_request['msg']:null
// 			];
// 			$order->saveOrUpdate();
			$payInfo = array(
				'trade_type'=>$tranInfo['transfer_pay_type'],
				'pay_remark' => $tranInfo['pay_remark'],
				'order_uuid'=>$tranInfo['transfer_num'],
				'pay_bank_account'=>$tranInfo['pay_bank_account'],
				'collect_bank_account'=>$tranInfo['collect_bank_account'],
				'to_name'=>$tranInfo['collect_account_name'],
				'to_bank_desc'=>$tranInfo['collect_bank_name'],
				'to_bank'=>$tranInfo['collect_bank'],	
				'to_city_name'=>AreaUtil::loadCityName($tranInfo['collect_city']),
				'to_bank_num'=>$tranInfo['collect_bank_link_code'],
				'notice_url'=>'com.jyblife.logic.bg.order.NoticeResult',
				'jmgUserName'=>$userInfo['username'],
				'jmgPassWord'=>$params['params']['jmgPassWord'],
				'amount'=>$tranInfo['amount']
			);
			
			$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.pay.Order", $payInfo);
			if(isset($ret['code'])&&$ret['code']!=0){
			    if ($ret['data']['status'] == SysTradeWater::STATUS_WAIT_CONFIRM) {
                    $params = [
                        'uuid' => $uuid,
                        'water_uuid' => $ret['data']['uuid'],
                        'pay_status' => PayTransfer::PAY_STATUS_UNCONFIRM
                    ];
                    $tran->params = $params;
                    $tran->saveOrUpdate();

                    $instance = new SysAuditFlowInstance();
                    $i = $instance->loadDatas(['instance_id' => $uuid, 'flow_uuid' => 7]);
                    if (!is_array($i) || count($i) == 0) {
                        throw new Exception('审批流不存在', ErrMsg::RET_CODE_SERVICE_FAIL);
                    }
                    $user_id = $i[0]['create_user_id'];
                    $u = SysUser::getUserInfoByIds([$user_id]);
                    $main_body = MainBody::getDataById($tranInfo['pay_main_body_uuid']);
                    $array = [
                        'business_type' => 'order',
                        'business_son_type' => 'transfer.confirm',
                        'content' => $u['name'] . '您好，付款调拨交易（' . $tranInfo['transfer_num'] . '）银企结果待手动确认，收款方为' . $main_body['full_name'] . '，付款金额为' . round($tranInfo['amount'] / 100, 2) . '，请登录系统进行处理',
                        'business_uuid' => $tranInfo['uuid'],
                        'send_datetime' => date('Y-m-d H:i:s'),
                        'create_time' => date('Y-m-d H:i:s'),
                        'deal_user_id' => $u[0]['user_id']
                    ];
                    $mail = $array;
                    $mail['title'] = '付款调拨交易待确认';
                    $mail['deal_user_name'] = $u[0]['name'];
                    $mail['email_address'] = $u[0]['email'];
                    $webDb = new SysWebNews();
                    $mailDb = new SysMailNews();
                    $webDb->addMsg($array);
                    $mailDb->addMsg($mail);
                } else {
                    $tran->params = [
                        'uuid'=>$uuid,
                        'water_uuid' => $ret['data']['uuid'],
                        'pay_status'=>PayTransfer::PAY_STATUS_FAIL,
                        'err_msg'=> $ret['data']['err_msg'],
                    ];
                    $tran->saveOrUpdate();

                    $r = [
                        'transfer_num'=>$tranInfo['transfer_num'],
                        'main_body_uuid'=>$tranInfo['pay_main_body_uuid'],
                        'transfer_create_time'=>date('Y-m-d H:i:s'),
                        'limit_date'=>$tranInfo['require_pay_datetime'],
                        'opt_uuid'=>$tranInfo['uuid'],
                        'trade_type'=>2
                    ];
                    EodTradeDb::dataCreate($r);
                    //审批流结束
                    $ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Approve',
                        ['flow_code'=>'pay_transfer_pay_type_2_code','instance_id'=>$tranInfo['uuid'],'approve_type'=>'2','info'=>$ret['data']['err_msg'],'sessionToken'=>$this->m_request['sessionToken']]);

                    if(empty($ret)||!isset($ret['code'])||$ret['code']!=0){
                        throw new Exception('审批流调用失败',ErrMsg::RET_CODE_SERVICE_FAIL);
                    }
                }
			}else{
				$date = date('Y-m-d');
				$tran->params = [
					'uuid'=>$tranInfo['uuid'],
					'water_uuid'=>$ret['data']['uuid'],
					'real_pay_date' =>$date,
				];
				$tran->saveOrUpdate();
				
				$order = new PayOrder();
				$order->params = [
					'uuid'=>$tranInfo['pay_order_uuid'],
					'real_pay_date' =>$date
				// 					'order_status'=>PayOrder::ORDER_STATUS_ARCHIVE,
				];
				$order->saveOrUpdate();
				EodTradeDb::dataOpted( $tranInfo ['uuid'], 2);
			}
			$tran->commit();
		}
		catch(Exception $e)
		{
			$tran->rollback();
			throw new Exception("付款指令审批下单失败|".$e->getMessage(),ErrMsg::RET_CODE_PAY_ORDER_APPROVE_ERROR);
		}
	}
	
	private function msgOpt(){
		if(empty($this->m_request['next_node_users'])){
			return;
		}
		
		$next_node_users = explode(",",$this->m_request['next_node_users']);
		if($this->m_request['node_code']=='Pay_transfer_approve_yq_2'){
			$cLogic = new CommonLogic();
			$logic = $cLogic->getAuditLog($this->m_request['instance_id'], ['pay_transfer_pay_type_2_code']);
			$next_node_users[] = $logic[1]['deal_user_id'];
		}
		$userInfos = SysUser::getUserInfoByIds($next_node_users);
		$users = array();
		foreach($userInfos as $u){
			$users[] = [
				'name'=>$u['name'],
				'id'=>$u['user_id'],
				'email'=>$u['email']
			];
		}
		$create_users = SysUser::getUserInfoByIds($this->m_request['create_user_id']);
		$create_user = [
			'name'=>$create_users[0]['name'],
			'id'=>$create_users[0]['user_id'],
			'email'=>$create_users[0]['email'],
		];
		
		$order_info = PayTransfer::getDataById($this->m_request['instance_id']);
		$req = [
			'next_audit_user_infos'=>$users,
			'transfer_num'=>$order_info['transfer_num'],
			'collect_main_body'=>$order_info['collect_main_body'],
			'amount'=>$order_info['amount'],
			'node_code'=>$this->m_request['node_code'],
			'cur_audit_control_type'=>$this->m_request['node_status'],
			'transfer_uuid'=>$this->m_request['instance_id'],
			'trade_type'=>$order_info['transfer_pay_type'],
			'create_user'=>$create_user,
			'create_user_id'=>$create_user['user_id'],
			'create_user_name'=>$create_user['name'],
			'create_user_email'=>$create_user['email']
		];
		$amqpUtil = new AmqpUtil();
		$ex = $amqpUtil->exchange(ORDER_EXCHANGE_NAME);
		return $ex->publish(json_encode($req), ORDER_ROUT_AUDIT_TRANSFER);
	}
}