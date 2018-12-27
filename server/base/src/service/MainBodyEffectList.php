<?php
/**
 * 	交易主体列表
 * 	@author sun
 *	@since 2018-03-28
 */
use money\service\BaseService;
use money\model\MainBody;
class MainBodyEffectList extends BaseService{
	protected $rule = [
		'sessionToken' => 'require',
		'status' => 'integer',
		'page' => 'integer',
		'limit' => 'integer',
	];
	
	public function exec(){
		$params = $this->m_request;
		$queryArray = array(
				'name'=>$this->getDataByArray($params, 'name'),
				'status'=>$this->getDataByArray($params, 'status'),
				'uuid'=>$this->getDataByArray($params, 'uuid'),
				'is_internal'=>$this->getDataByArray($params, 'is_internal')
		);
		$obj = new MainBody();
		$uuids = MainBody::getMainBodys($this->m_request['sessionToken']);
		if(count($uuids)==0){
			$result = ['page'=>$this->getDataByArray($params, 'page'), 'limit'=>$this->getDataByArray($params, 'limit'), 'count'=>0, 'data'=>[]];
			$this->packRet(ErrMsg::RET_CODE_SUCCESS, $result);
			return;
		}
		
		$queryArray['uuids'] = $uuids;
		$ret = $obj->details($queryArray,' * '
				,$this->getDataByArray($params, 'page'),$this->getDataByArray($params, 'limit'));
	
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
}

?>