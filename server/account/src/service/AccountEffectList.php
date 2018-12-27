<?php
/**
 * 账户列表
 * @author sun
 *
 */
use money\service\BaseService;
use money\model\BankAccount;
use money\model\MainBody;

class AccountEffectList  extends BaseService{
	protected $rule = [
		'sessionToken'=>'require',
		'page' => 'integer',
		'limit' => 'integer',
	];
	
	public function exec(){
		!isset($this->m_request['page']) && $this->m_request['page'] = 1;
		!isset($this->m_request['limit']) && $this->m_request['limit'] = 50;
		$params = $this->m_request;
		$queryArray = array(
				'main_body_uuid'=>$this->getDataByArray($params, 'main_body_uuid'),
				'bank_name'=>$this->getDataByArray($params, 'bank_name'),
				'real_pay_type'=>$this->getDataByArray($params, 'real_pay_type'),
				'status'=>'0',
				'deal_status'=>'1',
				'is_delete'=>'1'
		);
		
// 		$main_body_ids = MainBody::getMainBodys($this->m_request['sessionToken']);
// 		if(count($main_body_ids)==0){
// 			$result = ['page'=>$this->getDataByArray($params, 'page'), 'limit'=>$this->getDataByArray($params, 'limit'), 'count'=>0, 'data'=>[]];
// 			$this->packRet(ErrMsg::RET_CODE_SUCCESS, $result);
// 			return;
// 		}
// 		$queryArray['main_body_uuids'] = $main_body_ids;
		//过滤掉NULL值
		$queryArray = array_filter($queryArray, function ($v) {
			return $v !== null;
		});
		
		$obj = new BankAccount();
		$ret = $obj->details($queryArray,' * '
				,$this->getDataByArray($params, 'page'),$this->getDataByArray($params, 'limit'));
		$ret['data'] = MainBody::changeUuidToName($ret['data'] , 'main_body_uuid' , 'main_body_name');
		if(count($ret['data'])==0){
			$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
			return;
		}
	
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
}

?>