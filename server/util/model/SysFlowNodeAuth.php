<?php
/**
*	审批节点权限
*	@author sun
*	@since 2018-03-19
*/

namespace money\model;
use money\base\ParamsUtil;
class SysFlowNodeAuth extends BaseModel
{

	public $table = 'm_sys_flow_node_auth';

	//如果sessionToken为空，则判断节点权限是否为所有人都可以执行。
	public static function userRoleValidate($sessionToken , $node_info )
	{
		if(isset($sessionToken)){
			
// 			$userInfo = array(
// 				'code'=>'0',
// 				'data'=>array(
// 					'data'=>array(
// 						array(
// 							'role_uuid'=>'17918b25a6b8ec00b543c8ac79f37de9',
// 							'user_id'=>'1',
// 							'username'=>'张三'
// 						)

// 					)
// 				)

// 			);
			$sessionInfo = \JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.layer.SessionGet', ['sessionToken'=>$sessionToken]);
            if(!isset($sessionInfo['code']) || $sessionInfo['code'] != '0' || !isset($sessionInfo['data']['user_id'])){
                $code = isset($sessionInfo['code']) ? $sessionInfo['code'] : \ErrMsg::RET_CODE_SERVICE_FAIL;
                $msg = isset($sessionInfo['msg']) ? $sessionInfo['msg'] : '获取会话信息失败';
                throw new \Exception($msg, $code);
            }
			
			$userInfo = \JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.user.UserDetail'
					, ['user_id'=>$sessionInfo['data']['user_id'],'sessionToken'=>$sessionToken]);
			if(!isset($userInfo['code'])||!isset($userInfo['data'])||$userInfo['code']!=0){
				throw new \Exception('获取用户信息失败',\ErrMsg::RET_CODE_SERVICE_FAIL);
			}else if(!$userInfo['data']['user_id']){
				throw new \Exception('获取用户信息失败',\ErrMsg::RET_CODE_SERVICE_FAIL);
			}
			
			$role = ParamsUtil::getDataByArray($userInfo['data'], 'role');
			if(!$role||count($role)==0){
				throw new \Exception('当前用户没有角色，无法访问数据',\ErrMsg::RET_CODE_SERVICE_FAIL);
			}
			$role_uuids = ParamsUtil::getArrayByKey($role ,'uuid');

			$role_sql = " and (role_id in('".implode("','", $role_uuids)."') )";
		}else{
			$role_sql = " and role_id = 0";
		}
		$sql = "select count(1) cnt from m_sys_flow_node_auth"
			  ." where node_uuid = ".$node_info['uuid'].$role_sql;
        $obj = new static();
        $ret = $obj->query($sql);
		if($ret[0]['cnt']==0){
			throw new \Exception('权限验证失败',\ErrMsg::RET_CODE_FLOW_AUTH_VALIDATE_ERROR);
		}
		if(isset($sessionToken)){
			return $userInfo['data'];
		}else{
			return null;
		}
	}
	
	//获取节点权限列表
	public static function getRolesByNodeId($node_id){
		
		$obj = new static();
		$sql = "select group_concat(role_id)role_uuids from ".$obj->table." where node_uuid =:node_id";
		$ret = $obj->query($sql , array('node_id'=>$node_id));
		return $ret[0]['role_uuids'];
	}
}
