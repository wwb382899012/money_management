<?php
/**
*	付款指令审批流结果监听
*	@author sun
*	@since 2018-03-11
*/
use money\service\BaseService;
use money\model\SysUser;
use money\model\PayOrder;
use money\model\PayTransfer;
use money\model\MainBody;
use money\model\EodTradeDb;
class PayOrderFlowListener extends BaseService
{
    protected $rule = [
        //'sessionToken' => 'require',
        'node_code' => 'require',
        'status' => 'require|integer',
        'node_status' => 'require|integer',
    ];

	public function exec()	
	{
		$node_code = $this->m_request['node_code'];
		$node_status = $this->m_request['node_status'];
// 		if($node_code=='Pay_order_begin'){
// 			$this->packRet(ErrMsg::RET_CODE_SUCCESS);
// 			return;
// 		}
		
		$obj = new PayOrder();
		try{
            $obj->startTrans();
			if($node_code=='Pay_order_approve'){
				if($node_status==2){
					//付款指令
					$params = array();
					$params['uuid'] = $this->m_request['instance_id'];
					$params['is_financing'] = isset($this->m_request['params']['is_financing'])?$this->m_request['params']['is_financing']:null;
					$params['require_pay_datetime'] = isset($this->m_request['params']['require_pay_datetime'])?$this->m_request['params']['require_pay_datetime']:null;
					$uuid = $this->orderOpt($params);
				}else if($node_status==3){
					//只有指令的第一个节点审批驳回才会更新订单状态
					$obj = new PayOrder();
					$params['order_status'] = PayOrder::ORDER_STATUS_REJECT;
					$params['pay_status'] = PayOrder::PAY_STATUS_FAIL;
					$params['optor'] = $this->m_request['optor'];
					$params['opt_msg'] =  isset($this->m_request['msg'])?$this->m_request['msg']:null;
					$params['uuid'] = $this->m_request['instance_id'];
					$obj->params = $params;
					$obj->saveOrUpdate();

					EodTradeDb::dataOpted($this->m_request['instance_id'],1);
					
					$info = PayOrder::getDataById($this->m_request['instance_id']);
					$req = [
						'system_flag'=>$info['system_flag'],
						'uuid'=>$info['uuid'],
						'trade_type'=>1
					];
					$amqpUtil = new AmqpUtil();
					$ex = $amqpUtil->exchange(ORDER_RESULT_EXCHANGE_NAME);
					$ex->publish(json_encode($req), ORDER_RESULT_LISTENER);
					
				}else if($node_status==4){
					//只有指令的第一个节点审批驳回才会更新订单状态
					$obj = new PayOrder();
					$params['order_status'] = PayOrder::ORDER_STATUS_REFUSE;
					$params['pay_status'] = PayOrder::PAY_STATUS_FAIL;
					$params['optor'] = $this->m_request['optor'];
					$params['opt_msg'] =  isset($this->m_request['msg'])?$this->m_request['msg']:null;
					$params['uuid'] = $this->m_request['instance_id'];
					$obj->params = $params;
					$obj->saveOrUpdate();
					EodTradeDb::dataOpted($this->m_request['instance_id'], 1);
				}
			}else if($node_code=='Pay_order_approve_2'&&$node_status==3){
				$obj = new PayOrder();
				$params['order_status'] = PayOrder::ORDER_STATUS_WAITING;
				$params['uuid'] = $this->m_request['instance_id'];
				$obj->params = $params;
				$obj->saveOrUpdate();
					
				$tran = new PayTransfer();
				$trans = $tran->loadDatas(array('pay_order_uuid'=>$this->m_request['instance_id']));
				$params = array();
				$params['uuid'] = $trans[0]['uuid'];
				$params['transfer_status'] = PayTransfer::TRANSFER_STATUS_REJECT;
				$tran->params = $params;
				$tran->saveOrUpdate();
				$uuid = $trans[0]['uuid'];
				
				EodTradeDb::dataOpted($trans[0]['uuid'], 2);
				$order = $obj->getDataById($this->m_request['instance_id']);
				$r = [
					'out_order_num'=>$order['out_order_num'],
					'main_body_uuid'=>$order['pay_main_body_uuid'],
					'order_create_time'=>$order['create_time'],
					'order_opt_time'=>date('Y-m-d H:i:s'),
					'limit_date'=>$order['require_pay_datetime'],
					'opt_uuid'=>$order['uuid'],
					'trade_type'=>1
				];
				EodTradeDb::dataCreate($r);
				
			}else if($node_code=='Pay_order_approve_2'&&$node_status==4){
				$obj = new PayOrder();
				$params['order_status'] = PayOrder::ORDER_STATUS_REFUSE;
				$params['pay_status'] = PayOrder::PAY_STATUS_FAIL;
				$params['optor'] = $this->m_request['optor'];
				$params['opt_msg'] =  isset($this->m_request['msg'])?$this->m_request['msg']:null;
				$params['uuid'] = $this->m_request['instance_id'];
				$obj->params = $params;
				$obj->saveOrUpdate();
					
				$tran = new PayTransfer();
				$trans = $tran->loadDatas(array('pay_order_uuid'=>$this->m_request['instance_id']));
				$params = array();
				$params['uuid'] = $trans[0]['uuid'];
				$params['transfer_status'] = PayTransfer::TRANSFER_STATUS_REFUSE;
				$params['pay_status'] = PayTransfer::PAY_STATUS_FAIL;
				$tran->params = $params;
				$tran->saveOrUpdate();
				$uuid = $trans[0]['uuid'];
				EodTradeDb::dataOpted($trans[0]['uuid'], 2);
			}
			$obj->commit();
		}
		catch(Exception $e)
		{
			CommonLog::instance()->getDefaultLogger()->info('order flow listener error|msg:'.$e->getMessage());
			try{
                $obj->rollback();
			}catch(Exception $f){}
			throw new Exception("付款指令审批下单失败".$e->getMessage(),ErrMsg::RET_CODE_PAY_ORDER_APPROVE_ERROR);
		}

		$this->msgOpt();
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, array('transfer_uuid'=>$uuid));
	}

	public function orderOpt($params)
	{
		$obj = new PayOrder();
		$obj->params = $params;

		$obj->params['order_status'] = PayOrder::ORDER_STATUS_OPTED;
		$obj->saveOrUpdate();
		//生成调拨数据
		//如果驳回后再次处理，不生成新的调拨数据，只在原来数据基础上更新
		$data = PayOrder::getDataById($params['uuid']);
		$tran = new PayTransfer();
		$old_tran = $tran->loadDatas(['pay_order_uuid'=>$data['uuid']]);
		$params = array();
		if(is_array($old_tran)&&count($old_tran)>0){
			$params['uuid'] = $old_tran[0]['uuid'];
		}else{
			$mainBody = MainBody::getDataById($data['pay_main_body_uuid']);
			$params['transfer_num'] = PayTransfer::getTransferNum($mainBody['short_code']);
		}
		
		$params['pay_order_uuid'] = $data['uuid'];
		$params['system_flag'] = $data['system_flag'];
		$params['transfer_pay_type'] = $data['order_pay_type'];
		$params['pay_account_uuid'] = $data['pay_account_uuid'];
		$params['pay_main_body_uuid'] = $data['pay_main_body_uuid'];
		$params['pay_account_name'] = $data['pay_account_name'];
		$params['pay_bank_account'] = $data['pay_bank_account'];
		$params['pay_bank_name'] = $data['pay_bank_name'];
		
		$params['collect_main_body_uuid'] = $data['collect_main_body_uuid'];
		$params['collect_account_uuid'] = $data['collect_account_uuid'];
		$params['collect_main_body'] = $data['collect_main_body'];
		$params['collect_account_name'] = $data['collect_account_name'];
		$params['collect_bank_account'] = $data['collect_bank_account'];
// 		$params['collect_bank_desc'] = $data['collect_bank_desc'];
// 		$params['collect_bank'] = $data['collect_bank'];
		$params['collect_bank_desc'] = $data['collect_bank_desc'];
		$params['collect_city'] = $data['collect_city'];
		$params['collect_city_name'] = $data['collect_city_name'];
		$params['collect_bank'] = $data['collect_bank'];
		$params['collect_bank_address'] = $data['collect_bank_address'];
		$params['collect_bank_name'] = $data['collect_bank_name'];
		$params['collect_bank_link_code'] = $data['collect_bank_link_code'];
		
		$params['amount'] = $data['amount'];
		$params['currency'] = $data['currency'];
		$params['is_financing'] = $data['is_financing'];
		$params['financing_dict_key'] = $data['financing_dict_key'];
		$params['financing_dict_value'] = $data['financing_dict_value'];
		$params['bs_background'] = $data['bs_background'];
		$params['require_pay_datetime'] = $data['require_pay_datetime'];
		$params['order_create_people'] = $data['order_create_people'];
		$params['special_require'] = $data['special_require'];
		$params['plus_require'] = $data['plus_require'];
		
		$params['transfer_status'] = PayTransfer::TRANSFER_STATUS_WAITING;
		$params['pay_status'] = $data['pay_status'];
		$params['contact_annex'] = $data['contact_annex'];
		$params['create_user_id'] = $data['create_user_id'];
		$params['require_pay_datetime'] = $data['require_pay_datetime'];
		$tran->params = $params;
		$uuid = $tran->saveOrUpdate();
		
		EodTradeDb::dataOpted($this->m_request['instance_id'], 1);
		if(is_array($old_tran)&&count($old_tran)>0){
			$params = [
				'transfer_num'=>$old_tran[0]['transfer_num'],
				'main_body_uuid'=>$old_tran[0]['pay_main_body_uuid'],
				'order_create_time'=>$data['create_time'],
				'order_opt_time'=>date('Y-m-d H:i:s'),
				'limit_date'=>$old_tran[0]['require_pay_datetime'],
				'opt_uuid'=>$uuid,
				'trade_type'=>2
			];
		}else{
			$params = [
				'transfer_num'=>$params['transfer_num'],
				'main_body_uuid'=>$params['pay_main_body_uuid'],
				'order_create_time'=>$data['create_time'],
				'order_opt_time'=>date('Y-m-d H:i:s'),
				'limit_date'=>$params['require_pay_datetime'],
				'opt_uuid'=>$uuid,
				'trade_type'=>2
			];
		}
		EodTradeDb::dataCreate($params);
		return $uuid;
	}
	
	private function msgOpt(){
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
		
		$create_users = SysUser::getUserInfoByIds($this->m_request['create_user_id']);
		$create_user = [
		'name'=>$create_users[0]['name'],
		'id'=>$create_users[0]['user_id'],
		'email'=>$create_users[0]['email'],
		];
		$order_info = PayOrder::getDataById($this->m_request['instance_id']);
		$transfer = new PayTransfer();
		$transferInfo = $transfer->loadDatas(['pay_order_uuid'=>$this->m_request['instance_id']]);
		$req = [
			'next_audit_user_infos'=>$users,
			'order_num'=>$order_info['order_num'],
			'collect_main_body'=>$order_info['collect_main_body'],
			'amount'=>$order_info['amount'],
			'node_code'=>$this->m_request['node_code'],
			'cur_audit_control_type'=>$this->m_request['node_status'],
			'pay_uuid'=>$this->m_request['instance_id'],
			'create_user'=>$create_user,
			'create_user_id'=>$create_user['user_id'],
			'create_user_name'=>$create_user['name'],
			'create_user_email'=>$create_user['email']
		];
		if(is_array($transferInfo)&&count($transferInfo)>0){
			$req['transfer_num'] = $transferInfo[0]['transfer_num'];
			$req['transfer_uuid'] = $transferInfo[0]['uuid'];
		}
		$amqpUtil = new AmqpUtil();
		$ex = $amqpUtil->exchange(ORDER_EXCHANGE_NAME);
		return $ex->publish(json_encode($req), ORDER_ROUT_AUDIT);
	}

}