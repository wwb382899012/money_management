<?php
use money\service\BaseService;
use money\model\LoanTransfer;
use money\model\RepayOrder;
use money\model\SysUser;
use money\model\Repay;
use money\model\LoanCashFlow;
use money\model\MainBody;

class RepayCashFlowEditListener extends BaseService{
	protected $rule = [
		//'sessionToken' => 'require',
		'instance_id' => 'require',
	];
	
	public function exec(){
		$obj = new Repay();
		$obj->startTrans();
		try{
			if($this->m_request['node_code']=='Repay_flow_cash_edit_begin'){
				$repayInfo = Repay::getDataById($this->m_request['instance_id']);
				if(empty($repayInfo)){
					throw new Exception('还款id不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
				}
				if($repayInfo['repay_transfer_status']!=Repay::CODE_REPAY_TRANSFER_STATUS_WAITING){
					throw new Exception('还款数据处理中，无法修改现金流表',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
				}
				$transferInfo = LoanTransfer::getDataById($repayInfo['loan_transfer_uuid']);
				
				$repayOrder = new RepayOrder();
				$repayOrderArray = $repayOrder->loadDatas(['repay_id'=>$this->m_request['instance_id'],'repay_order_status'=>RepayOrder::ORDER_STATUS_OPTED]);
				
				if(!$repayOrderArray||count($repayOrderArray)==0){
					throw new Exception('还款指令不存在或审批中，无法修改现金流表',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
				}
				//添加前清除所有历史数据
				$f = new LoanCashFlow();
				$ret = $f->loadDatas([['loan_transfer_uuid','=',$repayInfo['loan_transfer_uuid']],['cash_status','in',[1,2,4]],['cash_flow_type','<>','1']],'uuid');
				if(is_array($ret)&&count($ret)>0){
					$ids = array_column($ret,'uuid');
					$f->where([['uuid','in',$ids]])->delete();
				}
				$amount = 0;
				if(isset($this->m_request['params']['repayCashDetail'])){
					$cashs = $this->m_request['params']['repayCashDetail'];
					if(is_array($cashs)){
						foreach($cashs as $cash){
							if(strtotime($cash['repay_date'])<strtotime(date('Y-m-d'))){
								throw new Exception('还款日期不能小于当日',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
							}
							$c = new LoanCashFlow();
							$params = array();
							$params['loan_transfer_uuid'] = $repayInfo['loan_transfer_uuid'];
							$params['index'] = $cash['index'];
							$params['cash_flow_type'] = $cash['cash_flow_type'];
							$params['amount'] = $cash['amount'];
							$params['real_amount'] = $cash['real_amount'];
							$params['info'] = $cash['info'];
							$params['cash_status'] = LoanCashFlow::STATUS_WAITING;
							$params['repay_date'] = $cash['repay_date'];
							$c->params = $params;
							$c->saveOrUpdate();
							if($cash['index']==$repayInfo['index']){
								$date = $cash['repay_date'];
							}
						}
					}
				}
				$obj->params = [
					'id'=>$this->m_request['instance_id'],
					'edit_status'=>Repay::CODE_EDIT_WAIT_APPROVE,
					'forecast_date'=>$date?$date:$repayInfo['forecast_date']
				];
				$obj->saveOrUpdate();
			}else if($this->m_request['node_code']=='Repay_flow_cash_edit_approve'){
				if($this->m_request['node_status']==2){
					$obj->params = [
						'id'=>$this->m_request['instance_id'],
						'edit_status'=>Repay::CODE_EDIT_NOT_EXISTS_ORDER
					];
					$obj->saveOrUpdate();
					
					$order = new RepayOrder();
					$orders = $order->loadDatas(['repay_id'=>$this->m_request['instance_id'],'repay_order_status'=>RepayOrder::REPAY_STATUS_OPTED]);
					
					if(is_array($orders)&&count($orders)>0){
						$order->params = [
							'id'=>$orders[0]['id'],
							'repay_order_status'=>RepayOrder::REPAY_STATUS_ARCHIVE
						];
						$order->saveOrUpdate();
					}
					
				}else if($this->m_request['node_status']==3){
					$obj->params = [
						'id'=>$this->m_request['instance_id'],
						'edit_status'=>Repay::CODE_EDIT_WAIT_EIDT
					];
					$obj->saveOrUpdate();
				}
			}
			
			$obj->commit();
			$this->msgOpt($this->m_request['instance_id']);
			$this->packRet ( ErrMsg::RET_CODE_SUCCESS, null );
		}catch(Exception $e){
			$obj->rollback();
 			throw new Exception('回调失败'.$e->getMessage(),$e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
		}
	}
	
	private function msgOpt($repay_id){
		if(empty($this->m_request['next_node_users'])){
			return;
		}
		$next_node_users = explode(",",$this->m_request['next_node_users']);
		$userInfos = SysUser::getUserInfoByIds($next_node_users);
	
		$users = array();
		foreach($userInfos as $u){
			$users[] = [
			'name'=>$u['name'],
			'id'=>$u['user_id'],
			'email'=>$u['email']
			];
		}
		
		$create_users = SysUser::getUserInfoByIds([$this->m_request['create_user_id']]);
		$create_user = [
		'name'=>$create_users[0]['name'],
		'id'=>$create_users[0]['user_id'],
		'email'=>$create_users[0]['email'],
		];
		
		$order_info = RepayOrder::getDataById($this->m_request['instance_id']);
		$main_body = MainBody::getDataById($order_info['collect_main_body_uuid']);
		
		$repayInfo = Repay::getDataById($repay_id);
		$loan_info = LoanTransfer::getDataById($repayInfo['loan_transfer_uuid']);
		
		$f = new LoanCashFlow();
		$ret = $f->loadDatas(['loan_transfer_uuid'=>$repayInfo['loan_transfer_uuid'],'index'=>$repayInfo['index']],'amount,cash_flow_type');
		$amount = 0;
		foreach($ret as $r){
			$amount+= $r['amount'];
		}
		
		$req = [
			'next_audit_user_infos'=>$users,
			'create_user_name'=>$this->m_request['create_user_name'],
			'transfer_num'=>$repayInfo['repay_transfer_num'],
			'collect_main_body'=>$loan_info['collect_main_body'],
			'amount'=>$amount,
			'node_code'=>$this->m_request['node_code'],
			'cur_audit_control_type'=>$this->m_request['node_status'],
			'transfer_uuid'=>$repayInfo['id'], //$this->m_request['instance_id'],
			'create_user'=>$create_user,
			'create_user_id'=>$create_user['id'],
			'create_user_name'=>$create_user['name'],
			'create_user_email'=>$create_user['email']
		];
		
		$req['real_pay_type'] = $ret[0]['real_pay_type'];
		\CommonLog::instance()->getDefaultLogger()->info('repay cash msg send|msg:'.json_encode($req));
		$amqpUtil = new AmqpUtil();
		$ex = $amqpUtil->exchange(LOAN_EXCHANGE_NAME);
		return $ex->publish(json_encode($req), REPAY_ROUT_AUDIT_CASH_FLOW);
	}
	
}

?>