<?php
use money\service\BaseService;
use money\model\SysTradeWater;
use money\model\LoanTransfer;
use money\model\BankAccount;
use money\model\SysUser;
use money\model\EodTradeDb;
use think\Validate;
use money\model\LoanCashFlow;
use money\model\Repay;
use money\model\RepayCashFlow;

class RepayFlowListener extends BaseService{

    protected $rule = [
        //'sessionToken' => 'require',
        'instance_id' => 'require',
    ];
	
	public function exec()
	{
		$tran = new LoanTransfer();
		
		$repayInfo = Repay::getDataById($this->m_request['instance_id']);
		$loan_info = LoanTransfer::getDataById($repayInfo['loan_transfer_uuid']);
		try{
            $tran->startTrans();
			switch($this->m_request['node_code'])
			{
				case "Repay_transfer_begin":
					if($repayInfo['edit_status']!=Repay::CODE_EDIT_NOT_EXISTS_ORDER){
						throw new \Exception('指令未审批或还款计划修改未结束，不能发起还款', \ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
					}
					$rule = [
						'real_pay_type' => 'require'
					];
					$validate = Validate::make($rule, [], []);
					if (!$validate->check($this->m_request['params'])) {
						throw new \Exception('参数错误:'.$validate->getError(), \ErrMsg::RET_CODE_GENVERIFY_TYPE_ERROR);
					}
					
					if($this->m_request['params']['real_pay_type']==2){
						$account = new BankAccount();
						$repay_account_id = isset($this->m_request['params']['repay_account_uuid'])?$this->getDataByArray($this->m_request['params'],'repay_account_uuid'):$repayInfo['repay_account_uuid'];
						$collect_account_id = isset($this->m_request['params']['collect_account_uuid'])?$this->getDataByArray($this->m_request['params'],'collect_account_uuid'):$repayInfo['collect_account_uuid'];
						$repay_account = $account->getDataById($repay_account_id);
						$collect_account = $account->getDataById($collect_account_id);
						
						if($this->m_request['params']['real_pay_type']==2&&in_array($repay_account['bank_dict_key'],[4,5])){
							//平安、农行收款账号行号不能为空
							if(empty($collect_account['bank_link_code'])){
								throw new Exception ( '付款银行平安或农行，收款账号行号不能为空，请维护账号后再提交', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR );
							}
						}
					}
					
					$repay = new Repay();
					$repay->params['id'] = $this->m_request['instance_id'];
					$repay->params['repay_status'] = Repay::CODE_REPAY_STATUS_WATING;
					$repay->params['repay_transfer_status'] = Repay::CODE_REPAY_TRANSFER_STATUS_MASTER_OPTED;
					$repay->params['repay_account_uuid'] = $this->getDataByArray($this->m_request['params'],'repay_account_uuid');
					$repay->params['collect_account_uuid'] =$this->getDataByArray($this->m_request['params'],'collect_account_uuid');  
					$repay->params['real_pay_type'] = $this->m_request['params']['real_pay_type'];
					$repay->params['pay_remark'] = $this->m_request['params']['pay_remark'];
					$repay->saveOrUpdate();
					
					
					
					$obj = new LoanTransfer();
					$obj->params['uuid'] = $repayInfo['loan_transfer_uuid'];
					$obj->params['cur_repay_id'] = $this->m_request['instance_id'];
					$obj->params['is_pay_off'] = 1;
					$obj->saveOrUpdate();
					
					$cash = new LoanCashFlow();
					$cs = $cash->loadDatas(['index'=>$repayInfo['index'],
							'loan_transfer_uuid'=>$repayInfo['loan_transfer_uuid']],'uuid');
					foreach($cs as $c){
						$cash->params = [
							'uuid'=>$c['uuid'],
							'cash_status'=>LoanCashFlow::STATUS_PAYING
						];
						$cash->saveOrUpdate();
					}
					
					$r = [
						'transfer_num'=>$repay->params['repay_transfer_num'],
						'main_body_uuid'=>$loan_info['loan_main_body_uuid'],
						'transfer_create_time'=>date('Y-m-d H:i:s'),
						'limit_date'=>$loan_info['forecast_datetime'],
						'opt_uuid'=>$this->m_request['instance_id'],
						'trade_type'=>6
					];
					EodTradeDb::dataCreate($r);
					break;
				case "Repay_transfer_approve_2":
					//付款
					$params = array();
					$params['id'] = $loan_info['cur_repay_id'];	
					$repay = new Repay();
					$repay->params = $params;
					if($this->m_request['node_status']==3){
						$repay->params['repay_transfer_status'] = Repay::CODE_REPAY_TRANSFER_STATUS_WAITING;
						$repay->saveOrUpdate();
						
						$cash = new LoanCashFlow();
						$cs = $cash->loadDatas(['index'=>$repayInfo['index'],
								'loan_transfer_uuid'=>$repayInfo['loan_transfer_uuid']],'uuid');
						foreach($cs as $c){
							$cash->params = [
							'uuid'=>$c['uuid'],
							'cash_status'=>LoanCashFlow::STATUS_WAITING
							];
							$cash->saveOrUpdate();
						}
// 						//清除所有历史数据
// 						$f = new RepayCashFlow();
// 						$ret = $f->loadDatas([['loan_transfer_uuid','=',$this->m_request['instance_id']],['status','in',[1,2,4]]],'id');
// 						if(is_array($ret)&&count($ret)>0){
// 							$ids = array_column($ret,'id');
// 							$f->where([['id','in',$ids]])->delete();
// 						}
						EodTradeDb::dataOpted($this->m_request['instance_id'], 6);
					}else if($this->m_request['node_status']==2){
						/**
						*	1、生成现金流表
						*	2、更新还款明细表还款现金流id
						*	3、更新还款明细状态为打款中（网银为打款成功）
						*	4、银企打款
						 */
						
						$repay = new Repay();
						$repay->params = [
							'id'=>$this->m_request['instance_id'],
							'repay_status'=>Repay::CODE_REPAY_STATUS_PAYING,
							'repay_transfer_status'=>Repay::CODE_REPAY_TRANSFER_STATUS_CONFIRMED
						];
						$repay->saveOrUpdate();

						if($repayInfo['real_pay_type']==1){
							$obj = new SysTradeWater();
							//2 保存数据，状态为等待提交
							$params = array();
							$params['trade_type'] = 16;
//							$params['pay_remark'] = $repayInfo['pay_remark'];
							$params['order_uuid'] = $repayInfo['repay_transfer_num'];
								
							//$params['trnuId'] = SysTradeWater::getTrnuId();
							$pay_account = BankAccount::getDataById($repayInfo['repay_account_uuid']);
							$collect_account = BankAccount::getDataById($repayInfo['collect_account_uuid']);
							$params['pay_account_uuid'] = $repayInfo['repay_account_uuid'];
							
							$params['pay_bank_key'] = $pay_account['bank_dict_key'];
							$params['pay_bank_account'] = $pay_account['bank_account'];
							$params['collect_bank_account'] = $collect_account['bank_account'];
							$params['to_name'] = $collect_account['account_name'];
							$params['to_bank_desc'] = $collect_account['bank_name'];
							$params['to_bank'] = $collect_account['bank_dict_key'];
							$params['to_city_name'] = $collect_account['city_name'];
								
							$params['amount'] = $repayInfo['amount'];
							$params['currency'] = 'CYN';
							$params['is_effective'] = SysTradeWater::STATUS_EFFECT;
							$params['status'] = SysTradeWater::STATUS_SUCCESS;
							$uuid = $obj->addWater($params);
							
							$repay->params = [
								'id'=>$this->m_request['instance_id'],
								'repay_water_uuid'=>$uuid
							];
							$repay->saveOrUpdate();
						}
						EodTradeDb::dataOpted($this->m_request['instance_id'], 6);
						if($repayInfo['real_pay_type']==2){
							//银企打款
							Repay::pay($loan_info['uuid'] ,$this->m_request['params']['jmgPassWord'] ,$this->m_request['sessionToken'] , $repayInfo);
						}
						else{
							//设为打款成功
							Repay::setSucc($loan_info['cur_repay_id']);
							$this->m_request['next_node_users'] = $this->m_request['create_user_id'];
						}
						
					}
					break;
				case 'Repay_transfer_approve_3':
					$tran->rollback();
					$this->msgOpt($this->m_request['instance_id']);
					$this->packRet(ErrMsg::RET_CODE_SUCCESS);
					return;
				default:
					break;
			}
            $tran->commit();
			
		}catch(Exception $e){
            $tran->rollback();
			throw new Exception('回调失败'.$e->getMessage(),$e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
		}
		$this->msgOpt($this->m_request['instance_id']);
		$this->packRet(ErrMsg::RET_CODE_SUCCESS);
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
		$repayInfo = Repay::getDataById($repay_id);
		$loan_info = LoanTransfer::getDataById($repayInfo['loan_transfer_uuid']);
		
		$f = new LoanCashFlow();
		$ret = $f->loadDatas(['loan_transfer_uuid'=>$repayInfo['loan_transfer_uuid'],'index'=>$repayInfo['index']],'amount,cash_flow_type');
		$amount = 0;
		foreach($ret as $r){
			$amount+= $r['amount'];
		}
		
		$create_users = SysUser::getUserInfoByIds([$this->m_request['create_user_id']]);
		$create_user = [
		'name'=>$create_users[0]['name'],
		'id'=>$create_users[0]['user_id'],
		'email'=>$create_users[0]['email'],
		];
		
		$req = [
			'next_audit_user_infos'=>$users,
			'create_user_name'=>$this->m_request['create_user_name'],
			'transfer_num'=>$repayInfo['repay_transfer_num'],
			'collect_main_body'=>$loan_info['collect_main_body'],
			'amount'=>$amount,
			'node_code'=>$this->m_request['node_code'],
			'cur_audit_control_type'=>$this->m_request['node_status'],
			'transfer_uuid'=>$this->m_request['instance_id'],
			'create_user'=>$create_user,
			'create_user_id'=>$create_user['id'],
			'create_user_name'=>$create_user['name'],
			'create_user_email'=>$create_user['email']
		];
		$req['real_pay_type'] = $repayInfo['real_pay_type'];
		$amqpUtil = new AmqpUtil();
		$ex = $amqpUtil->exchange(LOAN_EXCHANGE_NAME);
		return $ex->publish(json_encode($req), LOAN_ROUT_AUDIT_TRANSFER);
	}
}