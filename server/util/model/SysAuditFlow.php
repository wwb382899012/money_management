<?php
/**
*	流程配置类
*	@author sun
*	@since 2018-03-19
*/

namespace money\model;

class SysAuditFlow extends BaseModel
{
	protected $table = 'm_sys_audit_flow';


	//根据code获取流程详情
	public static function getDataByCode($flow_code)
	{
		$params = array(
			'flow_code'=>$flow_code
		);
		$o = new SysAuditFlow();
		$obj = $o->loadDatas($params);
		if(!is_array($obj)||count($obj)==0)
			return null;
		return $obj[0];
	}
}
