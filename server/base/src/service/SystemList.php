<?php

/**
 * 业务系统列表
 * @author sun
 * @since 2018-03-28
 */
use money\service\BaseService;
use money\base\MapUtil;
use money\model\InterfacePriv;

class SystemList extends BaseService{

    protected $rule = [
        'sessionToken'=>'require',
        'status'=>'integer',
    ];

	public function exec(){
		$params = $this->m_request;
		$queryArray = array(
				'sys_name'=>$this->getDataByArray($params, 'sys_name'),
				'status'=>$this->getDataByArray($params, 'status'),
				'uuid'=>$this->getDataByArray($params, 'uuid')
		);
		$obj = new InterfacePriv();
		$ret = $obj->details($queryArray,'uuid,system_flag,sys_name,ip_address,pwd_key,status '
				,$this->getDataByArray($params, 'page'),$this->getDataByArray($params, 'limit'));
// 		$ret = MapUtil::getMapdArrayByParams($ret['data'] , 'status' , 'interface_priv_status');
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
}
