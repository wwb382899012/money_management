<?php
/**
*	节点信息表
*	@author sun
*	@since 2018-03-19
*/

namespace money\model;

class SysFlowNode extends BaseModel
{
	const NODE_TYPE_BEGIN = 1;
	const NODE_TYPE_OPTING = 2;
	const NODE_TYPE_END = 3;

	public $table = 'm_sys_flow_node';

	public static function getStartNodeInfo($flow_code)
	{
		$obj = new static();
		$sql = "select n.*,f.flow_code from m_sys_flow_node n join m_sys_audit_flow f on n.flow_uuid = f.uuid where f.flow_code = :flow_code and node_type = 1";
        $ret = $obj->query($sql, ['flow_code'=>$flow_code]);
	
		if(!is_array($ret)||count($ret)==0)
			return array();
		return $ret[0];
	}

}
