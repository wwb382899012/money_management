<?php
/**
*	流程处理工具类
*	@author sun
*	@since 2018-03-05
*/
class FlowUtils
{
	/**
	*	发起流程
	*/
	public static function startFlow($flow_id , $intance_id)
	{

	}

	/**
	*	处理当前节点
	*	@param node_inst_id 节点实例id
	*	@param opt_type 处理类型 1、审批通过 2、审批拒绝
	*/
	public static function optFlow($node_inst_id , $opt_type)
	{

	}

	/**
	*	当前用户节点列表,包括用户所属角色下所有待处理数据
	*	@param userid 用户id
	*/
	public static function waitOptDataList($userid)
	{

	}

	/**
	*	当前用户历史处理数据列表
	*	@param userid 用户id
	*/
	public static function hisOptDataList($userid)
	{

	}

	/**
	*	数据详情
	*	@param inst_id 历史数据
	*	@param flow_id 流程id
	*/
	public static function dataDetail($inst_id,$flow_id)
	{

	}
}

?>