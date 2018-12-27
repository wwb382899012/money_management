<?php
use money\service\BaseService;
use money\model\BankAccount;
use money\model\BankAccountHis;
class ApplyListener extends BaseService{
	
	protected $rule = [
		'instance_id'=>'require',
		'flow_code'=>'require',
		'node_status'=>'require'
	];
	
	public function exec()
	{

		$instance_id = $this->m_request['instance_id'];
		$obj = new BankAccount();
		
		switch($this->m_request['flow_code']){
			case 'account_add_apply':
				if($this->m_request['status']==2){			
					$obj->params['deal_status'] = 1;
					$obj->params['uuid'] = $instance_id;
					$obj->saveOrUpdate();

                    $amqpUtil = new AmqpUtil();
                    $ex = $amqpUtil->exchange(MQ_EXCHANGE_ACCOUNT);
                    $ex->publish(json_encode(['uuid' => $instance_id, 'status' => 0, 'deal_status' => 1, 'has_history' => 0]), MQ_ROUT_ACCOUNT_NOTIFY);
				}else if($this->m_request['node_status']==3){
					$obj->params['deal_status'] = 2;
					$obj->params['uuid'] = $instance_id;
					$obj->saveOrUpdate();
				}
				break;
				
			case 'account_del_apply':
				if($this->m_request['status']==2){
					$obj->params['deal_status'] = 1;
					$obj->params['status'] = 2;
					$obj->params['uuid'] = $instance_id;
					$obj->saveOrUpdate();

                    $amqpUtil = new AmqpUtil();
                    $ex = $amqpUtil->exchange(MQ_EXCHANGE_ACCOUNT);
                    $ex->publish(json_encode(['uuid' => $instance_id, 'status' => 2, 'deal_status' => 1, 'has_history' => 0]), MQ_ROUT_ACCOUNT_NOTIFY);
				}else if($this->m_request['node_status']==3){
					$obj->params['deal_status'] = 1;
					$obj->params['uuid'] = $instance_id;
					$obj->saveOrUpdate();
				}
				break;		
						
			case 'account_enable_apply':
				if($this->m_request['status']==2){
					$obj->params['deal_status'] = 1;
					$obj->params['status'] = 0;
					$obj->params['uuid'] = $instance_id;
					$obj->saveOrUpdate();

                    $amqpUtil = new AmqpUtil();
                    $ex = $amqpUtil->exchange(MQ_EXCHANGE_ACCOUNT);
                    $ex->publish(json_encode(['uuid' => $instance_id, 'status' => 0, 'deal_status' => 1, 'has_history' => 0]), MQ_ROUT_ACCOUNT_NOTIFY);
				}else if($this->m_request['node_status']==3){
					$obj->params['deal_status'] = 1;
					$obj->params['uuid'] = $instance_id;
					$obj->saveOrUpdate();
				}
				break;
			
			case 'account_update_apply':
				if($this->m_request['node_status']==3){
					//历史表删除旧数据，
					$his = new BankAccountHis();
					$his_info = $his->field('*')->where(['uuid'=>$instance_id,'is_delete'=>1])->order('update_time desc')->limit('1')->find()->toArray();
					$info = BankAccount::getDataById($instance_id);
					$his->del($his_info['id']);
					
					$obj = new BankAccount();
					unset($his_info['status_time']);
					unset($his_info['id']);
					unset($his_info['optor_account']);
					unset($his_info['update_time']);
					unset($his_info['account_id']);
					$his_info['deal_status'] = 1;
					$obj->params = $his_info;
					$obj->saveOrUpdate();
				}else if($this->m_request['status']==2){
					$obj = new BankAccount();
					$obj->params['deal_status'] = 1;
					$obj->params['uuid'] = $instance_id;
					$obj->saveOrUpdate();

                    $amqpUtil = new AmqpUtil();
                    $ex = $amqpUtil->exchange(MQ_EXCHANGE_ACCOUNT);
                    $ex->publish(json_encode(['uuid' => $instance_id, 'status' => 0, 'deal_status' => 1, 'has_history' => 1]), MQ_ROUT_ACCOUNT_NOTIFY);
				}
				break;
		}
		$this->packRet(ErrMsg::RET_CODE_SUCCESS);
	}

	
}

?>