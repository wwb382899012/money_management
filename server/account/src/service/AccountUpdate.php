<?php
/**
 * 	账户更新
 * 	@author sun
 */
use money\service\BaseService;
use money\model\BankAccount;
use money\model\BankAccountHis;
class AccountUpdate  extends BaseService{

    protected $rule = [
        'sessionToken'=>'require',
        'uuid' => 'require',
    ];

	public function exec(){
		$obj = new BankAccount();
		$obj->startTrans();
		try{
		    if ($obj->isInUse($this->m_request['uuid'])) {
                throw new Exception('使用中的账户，不允许更新！', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
            }
			$info = $obj->getDataById($this->m_request['uuid']);
			
			if($info['deal_status']==0){
				throw new Exception('当前数据已经处理中不能重复处理！', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
			}
			
			//验证是否存在重复账号
			if($obj->validateDulicate($this->m_request['bank_name'],$this->m_request['bank_account'],$this->m_request['uuid'])){
	        	throw new Exception('银行账号不能重复！', ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
	        }
			
			$his = new BankAccountHis();
			$his->params = $info;
			$his->status_time = date('Y-m-d H:i:s');
			$his->saveOrUpdate();
			
	        isset($this->m_request['main_body_uuid']) && $params['main_body_uuid'] = $this->m_request['main_body_uuid'];
	        isset($this->m_request['short_name']) && $params['short_name'] = $this->m_request['short_name'];
	        isset($this->m_request['bank_name']) && $params['bank_name'] = $this->m_request['bank_name'];
	        isset($this->m_request['bank_account']) && $params['bank_account'] = $this->m_request['bank_account'];
	        isset($this->m_request['bank_dict_key']) && $params['bank_dict_key'] = $this->m_request['bank_dict_key'];
	        isset($this->m_request['account_name']) && $params['account_name'] = $this->m_request['account_name'];
	        isset($this->m_request['province']) && $params['province'] = $this->m_request['province'];
	        isset($this->m_request['city']) && $params['city'] = $this->m_request['city'];
	        isset($this->m_request['city_name']) && $params['city_name'] = $this->m_request['city_name'];
	        isset($this->m_request['area']) && $params['area'] = $this->m_request['area'];
	        isset($this->m_request['address']) && $params['address'] = $this->m_request['address'];
	        isset($this->m_request['interface_priv']) && $params['interface_priv'] = $this->m_request['interface_priv'];
	        isset($this->m_request['real_pay_type']) && $params['real_pay_type'] = $this->m_request['real_pay_type'];
	        isset($this->m_request['balance']) && $params['balance'] = $this->m_request['balance'];
	        isset($this->m_request['single_pay_limit']) && $params['single_pay_limit'] = $this->m_request['single_pay_limit'];
	        isset($this->m_request['bank_link_code']) && $params['bank_link_code'] = $this->m_request['bank_link_code'];
	        isset($this->m_request['status']) && $params['status'] = $this->m_request['status'];
	        isset($this->m_request['deal_status']) && $params['deal_status'] = $this->m_request['deal_status'];
	        
			$obj->params = $params;
			$obj->params['uuid'] = $this->m_request['uuid'];
			$obj->params['deal_status'] = 0;
			
			//$obj->params['optor_account'] = ? 等待用户接口修改后填入
			$obj->saveOrUpdate();
			
			//审批流发起
			$params = array(
					"flow_code"=>'account_update_apply',
					"instance_id"=>$this->m_request['uuid'],
					"main_body_uuid"=>$this->m_request['main_body_uuid'],
					"sessionToken"=>$this->m_request['sessionToken'],
					"info"=>'',
					'params'=>['opt_type'=>'add']
			);
			
			$ret = JmfUtil::call_Jmf_consumer("com.jyblife.logic.bg.flow.Start" , $params);
			if(!$ret||!isset($ret['code'])||$ret['code']!=0){
				throw new Exception('审批流发起错误');
			}
			$obj->commit();
			$this->packRet(ErrMsg::RET_CODE_SUCCESS, null);
			
		}catch(Exception $e){
			$obj->rollback();
			throw new Exception($e->getMessage()?$e->getMessage():'系统异常',$e->getCode()?$e->getCode():ErrMsg::RET_CODE_SERVICE_FAIL);
		}
	}
}