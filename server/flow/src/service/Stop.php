<?php

use money\service\BaseService;
use money\base\ParamsUtil;
use money\model\SysAuditFlow;
use money\model\SysAuditFlowInstance;
use money\model\SysAuditLog;
use money\model\SysFlowNode;
use money\model\SysFlowNodeAuth;

class Stop extends BaseService{
	protected $rule = [
		//'sessionToken' => 'require',
		'flow_code' => 'require',
		'instance_id' => 'require'
	];
	
	public function exec()
	{
		//获取流程信息。
		$flow_info = SysAuditFlow::getDataByCode($this->m_request["flow_code"]);
		if(!isset($flow_info)||!isset($flow_info['uuid']))
		{
			throw new \Exception('流程不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		//流程实例信息
		$flow_inst_info = SysAuditFlowInstance::getDataByInstId($flow_info["uuid"]
				,$this->m_request["instance_id"]);
		if(!isset($flow_inst_info)||!isset($flow_inst_info['uuid']))
		{
			throw new \Exception('流程实例不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
		}
		
		if($flow_inst_info['flow_instance_status']!=SysAuditFlowInstance::FLOW_STATUS_WAITING){
			throw new \Exception('当前流程实例已结束',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
		}
		//当前节点实例信息、节点信息
		$node_instance_info = SysAuditLog::loadCurNodeInfo($flow_inst_info["uuid"]);
		$node_info = SysFlowNode::getDataById($node_instance_info["node_uuid"]);
		
		if($node_info['need_auth_validate']==1){
			$sessionToken = isset($this->m_request['sessionToken'])?$this->m_request['sessionToken']:null;
			if(isset($sessionToken)){
				//权限验证，主体验证
				$userInfo = SysFlowNodeAuth::userRoleValidate($sessionToken ,  $node_info);
				//获取用户主体详情
				$main_body = isset($userInfo['main_body'])?$userInfo['main_body']:null;
				if(!$main_body||count($main_body)==0){
					throw new \Exception('当前用户没有设置主体，无法访问数据',ErrMsg::RET_CODE_SERVICE_FAIL);
				}
				$main_body_uuids = ParamsUtil::getArrayByKey($main_body ,'uuid');
				if(!in_array($flow_inst_info['main_body_uuid'] , $main_body_uuids)){
					throw new Exception("当前用户无权限对该数据操作",ErrMsg::RET_CODE_DATA_MAIN_BODY_AUTH_VALI_ERROR);
				}
			}else{
				throw new Exception('当前节点审批sessionToken不能为空',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
			}
		}
		
		$obj = new SysAuditLog();
		try{
            $obj->startTrans();
			$inst = new SysAuditFlowInstance();
			$inst->params = [
				'uuid'=>$flow_inst_info['uuid'],
				'flow_instance_status'=>SysAuditFlowInstance::FLOW_STATUS_APPROVED
			];
			$inst->saveOrUpdate();
			
			$nodeInst = new SysAuditLog();
			$nodeInst->params = [
				'uuid'=>$node_instance_info['uuid'],
				'deal_result'=>SysAuditLog::CODE_NODE_DEAL_RESULT_APPROVED,
				'deal_user_id'=>'',
			];
			if(isset($userInfo)){
				$nodeInst->params['deal_user_id'] = $userInfo['user_id'];
				$nodeInst->params['deal_user_name'] = $userInfo['username'];
			}
			$nodeInst->saveOrUpdate();
			
			
            $obj->commit();
       	}
       	catch(Exception $e)
       	{
            $obj->rollback();
            throw new Exception("审批流调用失败|".$e->getMessage(),ErrMsg::RET_CODE_SERVICE_FAIL);
       	}
       	if(isset($flow_info['server_name'])&&!empty($flow_info['server_name'])){
       		//调用接口通知。
       		$request_array = array(
       				'sessionToken'=>$this->m_request['sessionToken'],
       				'flow_code'=>$this->m_request['flow_code'],
       				'node_code'=>$node_info['node_code'],
       				'instance_id'=>$this->m_request['instance_id'],
       				'uuid'=>$flow_inst_info['uuid'],
       				'status'=>SysAuditFlowInstance::FLOW_STATUS_APPROVED,
       				'node_status'=>SysAuditLog::CODE_NODE_DEAL_RESULT_APPROVED,
       				'create_user_name'=>$flow_inst_info['create_user_name'],
       				'create_user_id'=>$flow_inst_info['create_user_id'],
       				'next_node_users'=>$flow_inst_info['create_user_id']
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
       			
       		}
       		if(isset($recall_ret['data'])&&!empty($recall_ret['data'])){
       			$ret['data'] = $recall_ret['data'];
       		}
       	}

       	$this->packRet(ErrMsg::RET_CODE_SUCCESS);
	}
}
