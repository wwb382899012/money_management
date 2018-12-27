<?php
use money\service\BaseService;
use money\model\LoanTransfer;
use money\model\SysUser;
use money\model\MainBody;
use money\model\EodTradeDb;
use money\model\RepayOrder;
use money\model\Repay;

class RepayOrderFlowListener extends BaseService{

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
// 			$this->packRet(ErrMsg::RET_CODE_SUCCESS, ['opted1']);
// 			return;
// 		}
	
		switch ($node_code){
			case 'Repay_order_begin':
				break;
			case 'Repay_order_approve':
				if($node_status==2){
					$obj = new RepayOrder();
					$params['repay_order_status'] = RepayOrder::REPAY_STATUS_OPTED;
					$params['id'] = $this->m_request['instance_id'];
					$obj->params = $params;
					$obj->saveOrUpdate();
					
					$repayInfo = $obj->getDataById($this->m_request['instance_id']);
					
					$repay = new Repay();
					$repay->params = [
						'id'=>$repayInfo['repay_id'],
						'edit_status'=>Repay::CODE_EDIT_WAIT_EIDT
					];
					$repay->saveOrUpdate();
					
					$tran = new LoanTransfer();
					$tranInfo = $tran->getDataById($repayInfo['loan_transfer_uuid']);
						
					$tran->params = [
						'uuid'=>$tranInfo['uuid'],
						'repay_order_time'=>date('Y-m-d')
					];
					$tran->saveOrUpdate();
					
					$e = new EodTradeDb();
					$repayEod = $e->loadDatas(['opt_uuid'=>$repayInfo['repay_id'],'trade_type'=>6]);
					if(is_array($repayEod)&&count($repayEod)>0){
						$e->params = [
						'id'=>$repayEod[0]['uuid'],
						'order_opt_time'=>date('Y-m-d H:i:s')
						];
						$e->saveOrUpdate();
					}
					
					EodTradeDb::dataOpted($this->m_request['instance_id'], 5);
					
				}else if($node_status==3){
					$obj = new RepayOrder();
					$params['repay_order_status'] = RepayOrder::REPAY_STATUS_REJECT;
					$params['id'] = $this->m_request['instance_id'];
					$obj->params = $params;
					$obj->saveOrUpdate();
					$repayInfo = $obj->getDataById($this->m_request['instance_id']);
					$repay = new Repay();
					$repay->params = [
						'id'=>$repayInfo['repay_id'],
						'edit_status'=>Repay::CODE_EDIT_NOT_EXISTS_ORDER
					];
					$repay->saveOrUpdate();
					EodTradeDb::dataOpted($repayInfo['uuid'], 5);
				}
				break;
			case 'Repay_order_wait_edit':
				$obj = new RepayOrder();
				$params['repay_order_status'] = RepayOrder::REPAY_STATUS_WAITING;
				$params['id'] = $this->m_request['instance_id'];
				$obj->params = $params;
				$obj->saveOrUpdate();
					
				$repayInfo = $obj->getDataById($this->m_request['instance_id']);
					
				$repay = new Repay();
					$repay->params = [
					'id'=>$repayInfo['repay_id'],
					'edit_status'=>Repay::CODE_EDIT_ORDER_APPROVEING
				];
				$repay->saveOrUpdate();
				EodTradeDb::dataOpted($repayInfo['uuid'], 5);
				break;
		}
		$this->msgOpt();
		EodTradeDb::dataOpted($this->m_request['instance_id'], 5);
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, null);
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
		
		$create_users = SysUser::getUserInfoByIds([$this->m_request['create_user_id']]);
		$create_user = [
			'name'=>$create_users[0]['name'],
			'id'=>$create_users[0]['user_id'],
			'email'=>$create_users[0]['email'],
		];

        $order_info = RepayOrder::getDataById($this->m_request['instance_id']);
        $main_body = MainBody::getDataById($order_info['collect_main_body_uuid']);

        if($this->m_request['node_code']=='Repay_order_approve'&&$this->m_request['node_status']==2){
            $loan_uuid = $order_info['repay_id'];
        }else{
            $loan_uuid = $this->m_request['instance_id'];
        }

        $req = [
            'next_audit_user_infos'=>$users,
            'create_user_name'=>$this->m_request['create_user_name'],
            'order_num'=>$order_info['order_num'],
            'collect_main_body'=>$main_body['full_name'],
            'amount'=>$order_info['amount'],
            'node_code'=>$this->m_request['node_code'],
            'cur_audit_control_type'=>$this->m_request['node_status'],
            'loan_uuid'=>$loan_uuid,
            'create_user'=>$create_user,
            'create_user_id'=>$create_user['id'],
            'create_user_name'=>$create_user['name'],
            'create_user_email'=>$create_user['email']
        ];
		$amqpUtil = new AmqpUtil();
		$ex = $amqpUtil->exchange(LOAN_EXCHANGE_NAME);
		CommonLog::instance()->getDefaultLogger()->info('repay news publish|msg:'.json_encode($req));
		return $ex->publish(json_encode($req), LOAN_ROUT_AUDIT);
	}

}