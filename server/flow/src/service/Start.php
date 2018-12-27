<?php
/**
*	发起流程
*	@author sun
*	@since 2018-03-15
*/
use money\service\BaseService;
use money\base\ParamsUtil;
use money\model\SysUser;
use money\model\SysAuditFlow;
use money\model\SysAuditFlowInstance;
use money\model\SysAuditLog;
use money\model\SysFlowNode;
use money\model\SysFlowNodeAuth;

class Start extends BaseService
{
    protected $rule = [
        //'sessionToken' => 'require',
        'flow_code' => 'require',
        'instance_id' => 'require',
        'main_body_uuid' => 'require',
    ];

	public function exec()
	{
		//获取流程信息、节点信息。
		$flow_info = SysAuditFlow::getDataByCode($this->m_request['flow_code']);
		if(empty($flow_info)||!isset($flow_info['uuid']))
		{
			throw new Exception("流程配置错误",ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		$node_info = SysFlowNode::getStartNodeInfo($this->m_request['flow_code']);
		if(empty($node_info))
		{
			throw new Exception("流程节点配置错误",ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		$n_node_info = SysFlowNode::getDataById($node_info['next_node_uuid']);

		//auth validate
		$sessionToken = isset($this->m_request['sessionToken'])?$this->m_request['sessionToken']:null;
		$main_body_uuid = $this->m_request['main_body_uuid'];
		
		if($node_info['need_auth_validate']==1){
			
			if(isset($sessionToken)){
				//如果sessionToken不为空，则需要判断主体权限
				$userInfo = SysFlowNodeAuth::userRoleValidate($sessionToken , $node_info);
				//获取用户主体详情
				$userDetail = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.user.UserDetail', 
						['user_id'=>$userInfo['user_id'],'sessionToken'=>$sessionToken]);
				
				if(!isset($userDetail['data']['main_body'])
					||!is_array($userDetail['data']['main_body'])
					||count($userDetail['data']['main_body'])==0){
					throw new Exception("当前用户无权限对该数据操作",ErrMsg::RET_CODE_DATA_MAIN_BODY_AUTH_VALI_ERROR);
				}
				$main_body_uuids =  ParamsUtil::getArrayByKey($userDetail['data']['main_body'] , 'uuid');
				if(!in_array($main_body_uuid,$main_body_uuids)){
					throw new Exception("当前用户无权限对该数据操作",ErrMsg::RET_CODE_DATA_MAIN_BODY_AUTH_VALI_ERROR);
				}
			}else{
				throw new Exception('当前节点审批sessionToken不能为空',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
			}
		}
		$obj = new SysAuditFlowInstance();
		try{
            $obj->startTrans();
		    
				//数据加锁
			$obj->params['flow_uuid'] = $flow_info['uuid'];
			$obj->params['instance_id'] = $this->m_request['instance_id'];
		    $ret = $obj->getLock();
		    if(!$ret||$ret<0){
				throw new Exception("当前审批流数据已被发起审批",ErrMsg::RET_CODE_FLOW_INSTANCE_ID_DULICATE);
		    }

			//创建流程、生成节点。
			$flow_instance = new SysAuditFlowInstance();
			$flow_instance->params = array(
				'flow_uuid'=>$flow_info['uuid'],
				'instance_id'=>$this->m_request['instance_id'],
				'flow_instance_status'=>SysAuditFlowInstance::FLOW_STATUS_WAITING,
				'main_body_uuid'=>$this->m_request['main_body_uuid'],
				'create_user_id'=>isset($userInfo['user_id'])?$userInfo['user_id']:0,
				'create_user_name'=>isset($userInfo['user_id'])?$userInfo['username']:''
			);
			$flow_inst_info = $flow_instance->loadDatas(['instance_id'=>$this->m_request['instance_id'],'flow_uuid'=>$flow_info['uuid']]);
			if(is_array($flow_inst_info)&&count($flow_inst_info)>0&&isset($flow_inst_info[0]['uuid'])){
				$flow_instance->params['uuid'] = $flow_inst_info[0]['uuid'];
				$flow_instance->saveOrUpdate();
				$uuid = $flow_inst_info[0]['uuid'];
			}else{
				$uuid = $flow_instance->saveOrUpdate();
			}
			//当前节点
			$flow_node_instance = new SysAuditLog();
			$flow_node_instance->params = array(
				'instance_uuid'=>$uuid,
				'node_uuid'=>$node_info['uuid'],
				'deal_result'=>SysAuditLog::CODE_NODE_DEAL_RESULT_APPROVED,
				'deal_remark'=>isset($this->m_request['info'])?$this->m_request['info']:null,
				'deal_user_id'=>$userInfo['user_id'] ?? 0,
				'deal_user_name'=>$userInfo['username'] ?? '',
				'create_user_id'=>$userInfo['user_id'] ?? 0,
				'create_user_name'=>$userInfo['username'] ?? ''
			);
			$flow_node_instance->saveOrUpdate();

			//下一节点
			$flow_node_instance = new SysAuditLog();
			$flow_node_instance->params = array(
				'instance_uuid'=>$uuid,
				'node_uuid'=>$n_node_info['uuid'],
				'deal_result'=>SysAuditLog::CODE_NODE_DEAL_RESULT_WAITING,
				'create_user_id'=>$userInfo['user_id'] ?? 0,
				'create_user_name'=>$userInfo['username'] ?? ''
			);
			$node_uuid = $flow_node_instance->saveOrUpdate();
			$flow_instance->params['node_instance_uuid'] = $node_uuid;
			$flow_instance->saveOrUpdate();

			//获取下一节点角色
			$next_node_role_uuids = SysFlowNodeAuth::getRolesByNodeId($n_node_info['uuid']);
			
			$ret = array(
				'uuid'=>$uuid
			);
			
			
            $obj->commit();
		    $obj->unlock();
		}catch(Exception $e){
            $obj->rollback();
		    $obj->unlock();
			throw $e;
		    //throw new Exception("审批流调用失败".$e->getMessage(),ErrMsg::RET_CODE_SERVICE_FAIL);
		}
		
		if(isset($flow_info['server_name'])&&!empty($flow_info['server_name'])){
			if(!empty($next_node_role_uuids)){
				$r = explode(',',$next_node_role_uuids);
				$u = new SysUser();
				$users = $u->getUserIdForMainUuidRoleId($this->m_request['main_body_uuid'] , $r);
			}
			//调用接口通知。
			$request_array = array(
					'sessionToken'=>isset($this->m_request['sessionToken'])?$this->m_request['sessionToken']:null,
					'flow_code'=>$this->m_request['flow_code'],
					'node_code'=>$node_info['node_code'],
					'instance_id'=>$this->m_request['instance_id'],
					'uuid'=>$uuid,
					'optor'=>$userInfo['username'] ?? '',
					'optor_id'=>$userInfo['user_id'] ?? 0,
					'next_node_role_uuids'=>$next_node_role_uuids,
					'status'=>SysAuditFlowInstance::FLOW_STATUS_WAITING,
					'next_node_users'=>$users,
					'node_status'=>SysAuditLog::CODE_NODE_DEAL_RESULT_APPROVED,
					'create_user_name'=>isset($userInfo['user_id'])?$userInfo['username']:'',
					'create_user_id'=>isset($userInfo['user_id'])?$userInfo['user_id']:0
			);
				
			if(isset($this->m_request['params'])){
				$request_array['params'] = $this->m_request['params'];
			}
				
			$recall_ret = JmfUtil::call_Jmf_consumer($flow_info['server_name'],$request_array);
			if(empty($recall_ret)||!isset($recall_ret['code'])){
				\CommonLog::instance()->getDefaultLogger()
					->error('审批流回调接口异常|server_name'.$flow_info['server_name'].'in:'.json_encode($request_array).'|out:'.json_encode($recall_ret));
			}
			if($recall_ret['code']!='0'){
				\CommonLog::instance()->getDefaultLogger()
				->error('审批流回调接口异常|server_name'.$flow_info['server_name'].'in:'.json_encode($request_array).'|out:'.json_encode($recall_ret));
				SysAuditLog::flowDataRollback($uuid, SysAuditLog::CODE_NODE_APPROVED);
				throw new Exception("审批流调用失败|".$recall_ret['msg'],ErrMsg::RET_CODE_SERVICE_FAIL);
			}
			if(isset($recall_ret['data'])&&!empty($recall_ret['data'])){
				$ret['data'] = $recall_ret['data'];
			}
		}
		$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
}
