<?php
/**
 * 审批流回调接口
 * @author sun
 *
 */
use money\service\BaseService;
use money\model\SysTradeWater;
use money\model\SysUser;
use money\model\MainBody;
use money\model\EodTradeDb;
use money\model\BankAccount;
use money\model\InnerTransfer;
use money\model\ReportFullTrade;
use money\model\SysAuditFlowInstance;
use money\model\SysWebNews;
use money\model\SysMailNews;
use money\logic\CommonLogic;

class InnerTransferListener extends BaseService{

    protected $rule = [
        //'sessionToken' => 'require',
        'instance_id' => 'require',
    ];

    public function exec()
	{
	if($this->m_request['flow_code']=='inner_transfer_pay_type_1_code'){
			switch($this->m_request['node_code'])
			{
				case "Inner_transfer_begin_wy":
// 					//资金专员
// 					$params = array();
// 					$params['uuid'] = $this->m_request['instance_id'];
// 					if(isset($this->m_request['params']['real_pay_type'])){
// 						$params['real_pay_type'] =  $this->m_request['params']['real_pay_type'];
// 					}
// 					if(isset($this->m_request['params']['annex_uuids'])){
// 						$params['annex_uuids'] =  $this->m_request['params']['annex_uuids'];
// 					}
					
// 					if(!isset($this->m_request['params']['pay_account_uuid'])){
// 						throw new Exception('打款账户不能为空',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
// 					}
					
// 					if(!isset($this->m_request['params']['collect_account_uuid'])){
// 						throw new Exception('收款账户不能为空',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
// 					}
					
// 					$oBankAmount = new BankAccount();
// 					$params['pay_account_uuid'] = $this->m_request['params']['pay_account_uuid'];
// 					$pay_bank_account = $oBankAmount->getDataById($params['pay_account_uuid']);
// 					$params['pay_bank_account'] = $pay_bank_account['bank_account'];
// 					$params['pay_account_name'] = $pay_bank_account['account_name'];
					
// 					if(isset($this->m_request['params']['collect_account_uuid'])&&!empty($this->m_request['params']['collect_account_uuid'])){
// 						$collect_bank_account = $oBankAmount->getDataById($this->m_request['collect_account_uuid']);
// 						$params['collect_account_name'] = $collect_bank_account['account_name'];
// 						$params['collect_bank_account'] = $$collect_bank_account['bank_account'];
// 						$params['collect_bank_desc'] = $collect_bank_account['bank_name'];
// 						$params['collect_bank_name'] = $collect_bank_account['short_name'];
// 						$params['collect_bank'] = $collect_bank_account['bank_dict_key'];
// 						$params['collect_city_name'] = $collect_bank_account['city_name'];						
// 					}else if(isset($this->m_request['collect_bank_account'])){
// 						$params['collect_account_name'] = $this->m_request['collect_account_name'];
// 						$params['collect_bank_account'] = $this->m_request['collect_bank_account'];
// 						$params['collect_bank_desc'] = $this->m_request['collect_bank_desc'];
// 						$params['collect_bank_name'] = $this->m_request['collect_bank_name'];
// 						$params['collect_bank'] = $this->m_request['collect_bank'];
// 						$params['collect_city_name'] = $this->m_request['collect_city_name'];
// 					}
// 					$params['transfer_status'] = InnerTransfer::TRANSFER_STATUS_OPTED;
					
// 					$tran = new InnerTransfer();
// 					$tran->params = $params;
// 					$tran->saveOrUpdate();
					break;
				case "Inner_transfer_approve_wy_1":
					$params = array();
					$params['uuid'] = $this->m_request['instance_id'];
					$tran = new InnerTransfer();
					if($this->m_request['node_status']==3){
						$params['transfer_status'] = InnerTransfer::TRANSFER_STATUS_REJECT;
						$tran->params = $params;
						$tran->saveOrUpdate();
						EodTradeDb::dataOpted($this->m_request['instance_id'], 7);
					}else{
						$params['transfer_status'] = InnerTransfer::TRANSFER_STATUS_OPTED;
						$tran->params = $params;
						$tran->saveOrUpdate();
					}
					break;
				case "Inner_transfer_approve_wy_2":
					//权签人
					
					$params = array();
					$params['uuid'] = $this->m_request['instance_id'];
					$tran = new InnerTransfer();
					if($this->m_request['node_status']==3)
					{
						$params['transfer_status'] = InnerTransfer::TRANSFER_STATUS_CHECK_REJECT;
						$tran->params = $params;
						$tran->saveOrUpdate();
						EodTradeDb::dataOpted($this->m_request['instance_id'], 7);
					}else{
						$params['transfer_status'] = InnerTransfer::TRANSFER_STATUS_ARCHIVE;
						$params['need_ticket_back'] = 1;
						$params['pay_status'] = InnerTransfer::INNER_STATUS_PAID;
						$params['real_deal_date'] = date('Y-m-d');
						$tran->params = $params;
						$tran->saveOrUpdate();
						
						$tranInfo = InnerTransfer::getDataById($this->m_request['instance_id']);
						//现金流表写入
						$oBankAmount = new BankAccount();
						$pay_bank_account = $oBankAmount->getDataById($tranInfo['pay_account_uuid']);
						
						$obj = new SysTradeWater();
						//2 保存数据，状态为等待提交
						$params = array();
						$params['trade_type'] = 18;
						$params['order_uuid'] = $tranInfo['order_num'];
						
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
						
						EodTradeDb::dataOpted($this->m_request['instance_id'], 7);
						$obj = new ReportFullTrade();
						$obj->saveData(2,$tranInfo['order_num']);
					}
					break;
				case "Inner_transfer_approve_wy_3":
					//付款
					$params = array();
					$params['uuid'] = $this->m_request['instance_id'];
					$params['bank_water'] = isset($this->m_request['params']['bank_water']) ? $this->m_request['params']['bank_water'] : '';
					$params['bank_img_file_uuid'] = isset($this->m_request['params']['bank_img_file_uuid']) ? $this->m_request['params']['bank_img_file_uuid'] : '';
					$params['need_ticket_back'] = 0;
					$tran = new InnerTransfer();
					$tran->params = $params;
					$params['uuid'] = $this->m_request['instance_id'];
					$tran->params['transfer_status'] = InnerTransfer::TRANSFER_STATUS_ARCHIVE;
					
					$tran->saveOrUpdate();
					
					$r = new ReportFullTrade();
					$reportInfo = $r->loadDatas(['trade_uuid'=>$this->m_request['instance_id']]);
					if(is_array($reportInfo)&&count($reportInfo)>0){
						$params = [
							'uuid'=>$reportInfo[0]['uuid'],
							'bank_water_no'=>isset($this->m_request['params']['bank_water']) ? $this->m_request['params']['bank_water'] : ''
						];
						$r->params = $params;
						$r->saveOrUpdate();
					}
					break;
				default:
					break;
			}
		}else if ($this->m_request['flow_code']=='inner_transfer_pay_type_2_code'){
			//付款银企
			switch($this->m_request['node_code'])
			{
				case "Inner_transfer_begin_yq":
// 					//资金专员
// 					$params = array();
// 					$params['uuid'] = $this->m_request['instance_id'];
// 					if(isset($this->m_request['params']['real_pay_type'])){
// 						$params['real_pay_type'] =  $this->m_request['params']['real_pay_type'];
// 					}
// 					if(isset($this->m_request['params']['annex_uuids'])){
// 						$params['annex_uuids'] =  $this->m_request['params']['annex_uuids'];
// 					}
					
// 					if(!isset($this->m_request['params']['pay_account_uuid'])){
// 						throw new Exception('打款账户不能为空',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
// 					}
					
// 					if(!isset($this->m_request['params']['collect_account_uuid'])){
// 						throw new Exception('收款账户不能为空',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
// 					}
					
// 					$oBankAmount = new BankAccount();
// 					$params['pay_account_uuid'] = $this->m_request['params']['pay_account_uuid'];
// 					$pay_bank_account = $oBankAmount->getDataById($params['pay_account_uuid']);
// 					$params['pay_bank_account'] = $pay_bank_account['bank_account'];
// 					$params['pay_account_name'] = $pay_bank_account['account_name'];
					
// 					if(isset($this->m_request['params']['collect_account_uuid'])&&!empty($this->m_request['params']['collect_account_uuid'])){
// 						$collect_bank_account = $oBankAmount->getDataById($params['collect_account_uuid']);
// 						$params['collect_account_name'] = $collect_bank_account['account_name'];
// 						$params['collect_bank_account'] = $$collect_bank_account['bank_account'];
// 						$params['collect_bank_desc'] = $collect_bank_account['bank_name'];
// 						$params['collect_bank_name'] = $collect_bank_account['short_name'];
// 						$params['collect_bank'] = $collect_bank_account['bank_dict_key'];
// 						$params['collect_city_name'] = $collect_bank_account['city_name'];						
// 					}else if(isset($this->m_request['collect_bank_account'])){
// 						$params['collect_account_name'] = $this->m_request['collect_account_name'];
// 						$params['collect_bank_account'] = $this->m_request['collect_bank_account'];
// 						$params['collect_bank_desc'] = $this->m_request['collect_bank_desc'];
// 						$params['collect_bank_name'] = $this->m_request['collect_bank_name'];
// 						$params['collect_bank'] = $this->m_request['collect_bank'];
// 						$params['collect_city_name'] = $this->m_request['collect_city_name'];
// 					}
// 					$params['transfer_status'] = InnerTransfer::TRANSFER_STATUS_OPTED;
// 					$tran = new InnerTransfer();
// 					$tran->params = $params;
// 					$tran->saveOrUpdate();
					break;
				case "Inner_transfer_approve_yq_1":
					if($this->m_request['node_status']==3)
					{
						$tran = new InnerTransfer();
						$params['uuid'] = $this->m_request['instance_id'];
						$params['transfer_status'] = InnerTransfer::TRANSFER_STATUS_REJECT;
						$tran->params = $params;
						$tran->saveOrUpdate();
						EodTradeDb::dataOpted($this->m_request['instance_id'], 7);
					}else if($this->m_request['node_status']==2){
						$tran = new InnerTransfer();
						$params = array();
						$params['uuid'] = $this->m_request['instance_id'];
						$params['transfer_status'] = InnerTransfer::TRANSFER_STATUS_OPTED;
						$tran->params = $params;
						$tran->saveOrUpdate();
					}
					break;
				case "Inner_transfer_approve_yq_2":
					//权签人
					$params = array();
					$params['uuid'] = $this->m_request['instance_id'];
					if($this->m_request['node_status']==3)
					{
						$tran = new InnerTransfer();
						$params['uuid'] = $this->m_request['instance_id'];
						$params['transfer_status'] = InnerTransfer::TRANSFER_STATUS_CHECK_REJECT;
						$params['real_deal_date'] = date('Y-m-d');
						$tran->params = $params;
						$tran->saveOrUpdate(); 
						EodTradeDb::dataOpted($this->m_request['instance_id'], 7);
					}
					else
					{
						$this->transferOpt($this->m_request);
						
					}
					break;
				case "Inner_transfer_approve_yq_3":
// 					//权签人
// 					if($this->m_request['node_status']==3){
// 						$tran = new InnerTransfer();
// 						$params = array();
// 						$params['uuid'] = $this->m_request['instance_id'];
// 						$params['transfer_status'] = InnerTransfer::TRANSFER_STATUS_CHECK_REJECT;
// 						$params['pay_status'] = InnerTransfer::INNER_STATUS_UNPAID;
// 						$tran->params = $params;
// 						$tran->saveOrUpdate();
// 					}
					break;
				default:
					return;
			}
		}
		$this->msgOpt();
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, null);
	}

	public function transferOpt($params)
	{
		$uuid = $params['instance_id'];
		
		if(!isset($params['params']['jmgPassWord'])){
			throw new Exception('加密狗密码不能为空');
		}
		$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.layer.SessionGet", array('sessionToken'=>$params['sessionToken']));
		if(!isset($ret['code'])||$ret['code']!=0||empty($ret['data'])){
			throw new Exception('无法获取用户信息');
		}
		$userInfo = $ret['data'];
		$tran = new InnerTransfer();
        $tran->startTrans();
		try
		{	
			$tran->params['uuid'] = $uuid;
			$tran->params['transfer_status'] = InnerTransfer::TRANSFER_STATUS_COMFIRMED;
			$tran->params['pay_status'] = InnerTransfer::INNER_STATUS_PAYING;
			$tran->saveOrUpdate();
			
			$tranInfo = $tran->getDataById($uuid);
			$obj = new BankAccount();
			$pay_account = $obj->getDataById($tranInfo['pay_account_uuid']);
			$collect_account = $obj->getDataById($tranInfo['collect_account_uuid']);
			
			$payInfo = array(
				'trade_type'=>18,
				'pay_remark' => $tranInfo['pay_remark'],
				'order_uuid'=>$tranInfo['order_num'],
				'pay_bank_account'=>$pay_account['bank_account'],
				'collect_bank_account'=>$tranInfo['collect_bank_account'],
				'to_name'=>$tranInfo['collect_account_name'],
				'to_bank_desc'=>$tranInfo['collect_bank_name'],
				'to_bank'=>$tranInfo['collect_bank'],
				'to_city_name'=>$tranInfo['collect_city_name'],
				'to_bank_num'=>$collect_account['bank_link_code'],
				'notice_url'=>'com.jyblife.logic.bg.inner.NoticeResult',
				'jmgUserName'=>$userInfo['username'],
				'jmgPassWord'=>$params['params']['jmgPassWord'],
				'amount'=>$tranInfo['amount']
			);
			
			$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.pay.Order", $payInfo);
			if(isset($ret['code'])&&$ret['code']!=0){
				$params = [
					'uuid'=>$uuid,
					'water_uuid'=>$ret['data']['uuid'],
					'pay_status'=>InnerTransfer::INNER_STATUS_UNCONFIRM
				];
				$tran->params = $params;
				$tran->saveOrUpdate();
				
				$instance = new SysAuditFlowInstance();
				$i = $instance->loadDatas(['instance_id'=>$uuid,'flow_uuid'=>14]);
				if(!is_array($i)||count($i)==0){
					throw new Exception('审批流不存在',ErrMsg::RET_CODE_SERVICE_FAIL);
				}
				$user_id = $i[0]['create_user_id'];
				$u = SysUser::getUserInfoByIds([$user_id]);
				$main_body = MainBody::getDataById($tranInfo['main_body_uuid']);
				$array = [
					'business_type'=>'order',
					'business_son_type'=>'transfer.confirm',
					'content'=>$u['name'].'您好，内部调拨交易（'.$tranInfo['order_num'].'）银企结果待手动确认，收款方为'.$main_body['full_name'].'，付款金额为'.round($tranInfo['amount']/100,2).'，请登录系统进行处理',
					'business_uuid'=>$tranInfo['uuid'],
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
			}else{
				$tran->params = [
					'uuid'=>$tranInfo['uuid'],
					'water_uuid'=>$ret['data']['uuid']
				];
				$tran->saveOrUpdate();
				EodTradeDb::dataOpted($tranInfo['uuid'], 7);
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
		if($this->m_request['node_code']=='Inner_transfer_approve_yq_3'){
			$cLogic = new CommonLogic();
			$logic = $cLogic->getAuditLog($this->m_request['instance_id'], ['inner_transfer_pay_type_2_code']);
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
		
		$order_info = InnerTransfer::getDataById($this->m_request['instance_id']);
		$main_body = MainBody::getDataById($order_info['main_body_uuid']);
		
		$s = new SysUser();
		$user_str = $s->getUserIdForMainUuidRoleId($order_info['main_body_uuid'],['00cc4afb2f67592ba520e5bfcafc7034']);
		$au_array= explode(',',$user_str);
		$au = SysUser::getUserInfoByIds($au_array);
		$approve_users = [];
		foreach($au as $u){
			$approve_users[] = [
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
		
		$req = [
			'next_audit_user_infos'=>$users,
			'create_user_name'=>$this->m_request['create_user_name'],
			'order_num'=>$order_info['order_num'],
			'collect_main_body'=>$main_body['full_name'],
			'amount'=>$order_info['amount'],
			'node_code'=>$this->m_request['node_code'],
			'cur_audit_control_type'=>$this->m_request['node_status'],
			'inner_uuid'=>$this->m_request['instance_id'],
			'approve_users'=>$approve_users,
			'create_user'=>$create_user,
			'create_user_id'=>$create_user['id'],
			'create_user_name'=>$create_user['name'],
			'create_user_email'=>$create_user['email']
		];
		$amqpUtil = new AmqpUtil();
		$ex = $amqpUtil->exchange(INNER_EXCHANGE_NAME);
		return $ex->publish(json_encode($req), INNER_ROUT_AUDIT);
	}
}