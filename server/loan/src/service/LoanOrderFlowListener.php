<?php
/**
 *	借款指令审批流结果监听
 *	@author sun
 *	@since 2018-04-19
 */
use money\service\BaseService;
use money\model\SysUser;
use money\model\LoanTransfer;
use money\model\MainBody;
use money\model\EodTradeDb;
use money\model\LoanOrder;

class LoanOrderFlowListener extends BaseService {

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
		$obj = new LoanOrder();
		$ret = array();
		switch ($node_code){
			case 'Loan_order_begin':
				break;
			case 'Loan_order_approve':
				$orderInfo = $obj->getDataById($this->m_request['instance_id']);
				if($node_status==2){
					$params = array();
					$params['uuid'] = $this->m_request['instance_id'];
					$uuid = $this->orderOpt($params);
					$ret['transfer_uuid'] = $uuid;
				}else if($node_status==3){
					$obj = new LoanOrder();
					$params['order_status'] = LoanOrder::ORDER_STATUS_REJECT;
					$params['loan_status'] = LoanOrder::LOAN_STATUS_FAIL;
					$params['uuid'] = $this->m_request['instance_id'];
					$obj->params = $params;
					$obj->saveOrUpdate();
					EodTradeDb::dataOpted($this->m_request['instance_id'], 3);
				}
				break;
			case 'Loan_order_approve_2':
				$orderInfo = $obj->getDataById($this->m_request['instance_id']);
				if($node_status==3){
					$obj = new LoanOrder();
					$params['order_status'] = LoanOrder::ORDER_STATUS_WAITING;
					$params['uuid'] = $this->m_request['instance_id'];
					$obj->params = $params;
					$uuid = $obj->saveOrUpdate();
						
					$tran = new LoanTransfer();
					$trans = $tran->loadDatas(array('loan_order_uuid'=>$this->m_request['instance_id']));
					$params = array();
					$params['uuid'] = $trans[0]['uuid'];
					$params['transfer_status'] = LoanTransfer::TRANSFER_STATUS_REJECT;
					$tran->params = $params;
					$tran->saveOrUpdate();
					EodTradeDb::dataOpted($trans[0]['uuid'], 4);
					
					$r = [
						'out_order_num'=>$orderInfo['out_order_num'],
						'main_body_uuid'=>$orderInfo['loan_main_body_uuid'],
						'limit_date'=>$orderInfo['loan_date_time'],
						'opt_uuid'=>$orderInfo['uuid'],
						'trade_type'=>3
					];
					EodTradeDb::dataCreate($r);
				}else if($node_status==4){
					$obj = new LoanOrder();
					$params['order_status'] = LoanOrder::ORDER_STATUS_REFUSE;
					$params['loan_status'] = LoanOrder::LOAN_STATUS_FAIL;
					$params['uuid'] = $this->m_request['instance_id'];
					$obj->params = $params;
					$uuid = $obj->saveOrUpdate();
					
					$tran = new LoanTransfer();
					$trans = $tran->loadDatas(array('loan_order_uuid'=>$this->m_request['instance_id']));
					$params = array();
					$params['uuid'] = $trans[0]['uuid'];
					$params['transfer_status'] = LoanTransfer::TRANSFER_STATUS_REFUSE;
					$params['loan_status'] = LoanTransfer::LOAN_STATUS_FAIL;
					$tran->params = $params;
					$tran->saveOrUpdate();

					EodTradeDb::dataOpted($trans[0]['uuid'], 4);
				}
				break;
		}
		$this->msgOpt();
		$this->packRet(ErrMsg::RET_CODE_SUCCESS,$ret);
		return;
	}

	public function orderOpt($params)
	{
		$obj = new LoanOrder();
		$obj->params = $params;

	
		try
		{
            $obj->startTrans();

			$obj->params['order_status'] = LoanOrder::ORDER_STATUS_OPTED;
			$obj->saveOrUpdate();
			//生成调拨数据
			$data = LoanOrder::getDataById($params['uuid']);
			
			
			$tran = new LoanTransfer();
			$ts = $tran->loadDatas(['loan_order_uuid'=>$params['uuid']]);
			
			$params = array();
			if(is_array($ts)&&count($ts)>0){
				$params['uuid'] = $ts[0]['uuid'];
				$params['loan_status'] = LoanTransfer::LOAN_STATUS_UNPAID;
				$params['transfer_status'] = LoanTransfer::TRANSFER_STATUS_WAITING;
			}else{
				$mainBody = MainBody::getDataById($data['loan_main_body_uuid']);
				$params['transfer_num'] = LoanTransfer::getTransferNum($mainBody['short_code']);
			}
			
			$params['loan_order_uuid'] = $data['uuid'];
			$params['system_flag'] = $data['system_flag'];
			$params['loan_order_num'] = $data['order_num'];
			$params['loan_account_uuid'] = $data['loan_account_uuid'];
			$params['loan_main_body_uuid'] = $data['loan_main_body_uuid'];
			$params['loan_account_name'] = $data['loan_account_name'];
			$params['loan_bank_account'] = $data['loan_bank_account'];
			$params['loan_bank_name'] = $data['loan_bank_name'];
			
			$params['collect_main_body_uuid'] = $data['collect_main_body_uuid'];
			$params['collect_account_uuid'] = $data['collect_account_uuid'];
			$params['collect_main_body'] = $data['collect_main_body'];
			$params['collect_account_name'] = $data['collect_account_name'];
			$params['collect_bank_account'] = $data['collect_bank_account'];
			// 		$params['collect_bank_desc'] = $data['collect_bank_desc'];
			// 		$params['collect_bank'] = $data['collect_bank'];
			$params['collect_bank_name'] = $data['collect_bank_name'];
			
			$params['amount'] = $data['amount'];
			$params['rate'] = $data['rate'];
			$params['currency'] = $data['currency'];
			$params['forecast_datetime'] = $data['forecast_datetime'];
			$params['bs_background'] = $data['bs_background'];
			$params['loan_datetime'] = $data['loan_datetime'];
			$params['transfer_create_people'] = $data['order_create_people'];
			$params['plus_require'] = $data['plus_require'];
			$params['contact_annex'] = $data['contact_annex'];
			$params['loan_type'] = $data['loan_type'];
		
			$params['create_user_id'] = $data['create_user_id'];
			
			$tran->params = $params;
			$uuid = $tran->saveOrUpdate();
			
			EodTradeDb::dataOpted($this->m_request['instance_id'], 3);
			if(is_array($ts)&&count($ts)>0){
				$r = [
					'transfer_num'=>$ts[0]['transfer_num'],
					'main_body_uuid'=>$ts[0]['loan_main_body_uuid'],			
					'order_create_time'=>$data['create_time'],
					'order_opt_time'=>date('Y-m-d H:i:s'),
					'limit_date'=>$ts[0]['loan_datetime'],
					'opt_uuid'=>$ts[0]['uuid'],
					'trade_type'=>4
				];
			}else{
				$r = [
					'transfer_num'=>$params['transfer_num'],
					'main_body_uuid'=>$params['loan_main_body_uuid'],
					'order_create_time'=>$data['create_time'],
					'order_opt_time'=>date('Y-m-d H:i:s'),
					'limit_date'=>$params['loan_datetime'],
					'opt_uuid'=>$uuid,
					'trade_type'=>4
				];
			}
			CommonLog::instance()->getDefaultLogger()->info(json_encode($r));
			EodTradeDb::dataCreate($r);
            $obj->commit();
			return $uuid;
		}
		catch(Exception $e)
		{
            $obj->rollback();
			throw new Exception("付款指令审批下单失败".$e->getMessage(),ErrMsg::RET_CODE_PAY_ORDER_APPROVE_ERROR);
		}
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
	
		$order_info = LoanOrder::getDataById($this->m_request['instance_id']);
		$transfer = new LoanTransfer();
		$transferInfo = $transfer->loadDatas(['loan_order_uuid'=>$this->m_request['instance_id']]);
		$req = [
			'next_audit_user_infos'=>$users,
			'create_user_name'=>$this->m_request['create_user_name'],
			'order_num'=>$order_info['order_num'],
			'collect_main_body'=>$order_info['collect_main_body'],
			'amount'=>$order_info['amount'],
			'node_code'=>$this->m_request['node_code'],
			'cur_audit_control_type'=>$this->m_request['node_status'],
			'loan_uuid'=>$this->m_request['instance_id']
		];
		if(is_array($transferInfo)&&count($transferInfo)>0){
			$req['transfer_num'] = $transferInfo[0]['transfer_num'];
			$req['transfer_uuid'] = $transferInfo[0]['uuid'];
		}
		$amqpUtil = new AmqpUtil();
		$ex = $amqpUtil->exchange(LOAN_EXCHANGE_NAME);
		return $ex->publish(json_encode($req), LOAN_ROUT_AUDIT);
	}
}