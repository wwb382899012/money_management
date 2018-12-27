<?php
/**
*	审批节点
*	@author sun
*	@since 2018-03-20
*/
use money\service\BaseService;
use money\model\SysUser;
use money\base\ParamsUtil;
use money\model\SysAuditFlow;
use money\model\SysAuditFlowInstance;
use money\model\SysAuditLog;
use money\model\SysFlowNode;
use money\model\SysFlowNodeAuth;

class Approve extends BaseService
{
    protected $rule = [
        //'sessionToken' => 'require',
        'flow_code' => 'require',
        'instance_id' => 'require',
        'approve_type' => 'require',
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
		
		//当前节点实例信息、节点信息
		$node_instance_info = SysAuditLog::loadCurNodeInfo($flow_inst_info["uuid"]);
		$node_info = SysFlowNode::getDataById($node_instance_info["node_uuid"]);

		//非结束节点获取下一节点信息
		if($node_info['node_type']!=SysFlowNode::NODE_TYPE_END&&$this->m_request['approve_type']==SysAuditLog::CODE_NODE_APPROVED)
		{
			$n_node_info = SysFlowNode::getDataById($node_info['next_node_uuid']);
		}
		else if ($this->m_request['approve_type']==SysAuditLog::CODE_NODE_REJECT)
		{
			$n_node_info = SysFlowNode::getDataById($node_info['pre_node_uuid']);
			
		}
		
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
            //更新旧节点
            $log = new SysAuditLog();
            $node_status = $this->m_request['approve_type']==SysAuditLog::CODE_NODE_APPROVED?
            	SysAuditLog::CODE_NODE_DEAL_RESULT_APPROVED:SysAuditLog::CODE_NODE_DEAL_RESULT_REFUSE;
            switch($this->m_request['approve_type']){
            	case SysAuditLog::CODE_NODE_APPROVED:
            		$node_status = SysAuditLog::CODE_NODE_DEAL_RESULT_APPROVED;
            		$status = $node_info['node_type']==SysFlowNode::NODE_TYPE_END?SysAuditFlowInstance::FLOW_STATUS_APPROVED:SysAuditFlowInstance::FLOW_STATUS_WAITING;
            		break;
            	case SysAuditLog::CODE_NODE_REJECT:
            		$node_status = SysAuditLog::CODE_NODE_DEAL_RESULT_REJECT;
            		if(isset($n_node_info)){
            			$status = $node_info['node_type']==SysFlowNode::NODE_TYPE_BEGIN?SysAuditFlowInstance::FLOW_STATUS_REFUSED:SysAuditFlowInstance::FLOW_STATUS_WAITING;
            		}else{
            			$status = SysAuditFlowInstance::FLOW_STATUS_REFUSED;
            		}
            		
            		break;
            	case SysAuditLog::CODE_NODE_REFUSED:
            		$node_status = SysAuditLog::CODE_NODE_DEAL_RESULT_REFUSE;
            		$status = SysAuditFlowInstance::FLOW_STATUS_REFUSED;
            		break;
            }
            $log->params = array(
            	'uuid'=>$node_instance_info['uuid'],
            	'deal_result'=>$node_status,
            	'deal_remark'=>isset($this->m_request['info'])?$this->m_request['info']:null,
       	     	'deal_user_id'=>$userInfo['user_id']??0,
				'deal_user_name'=>$userInfo['username'],
            );
            $log->saveOrUpdate();

            //结束节点，更新流程状态
            if($node_info['node_type']==SysFlowNode::NODE_TYPE_END&&$this->m_request['approve_type']==SysAuditLog::CODE_NODE_APPROVED)
            {
            	$instance = new SysAuditFlowInstance();
            	$instance->params = array(
            		'uuid'=>$flow_inst_info['uuid'],
            		'flow_instance_status'=>SysAuditFlowInstance::FLOW_STATUS_APPROVED
            	);
            	$instance->saveOrUpdate();
				$redirect_rule = $node_info["redirect_rule"];
				//获取下一节点角色
				$next_node_role_uuids = null;
				$users = $flow_inst_info['create_user_id'];
            }else if(($n_node_info['node_type']==SysFlowNode::NODE_TYPE_BEGIN&&$this->m_request['approve_type']==SysAuditLog::CODE_NODE_REJECT)){
            	$instance = new SysAuditFlowInstance();
            	$instance->params = array(
            			'uuid'=>$flow_inst_info['uuid'],
            			'flow_instance_status'=>SysAuditFlowInstance::FLOW_STATUS_REFUSED
            	);
            	$instance->saveOrUpdate();
            	$redirect_rule = $node_info["redirect_rule"];
            	//获取下一节点角色
            	$next_node_role_uuids = SysFlowNodeAuth::getRolesByNodeId($n_node_info['uuid']);
            }else if($this->m_request['approve_type']==SysAuditLog::CODE_NODE_REFUSED){
            	$instance = new SysAuditFlowInstance();
            	$instance->params = array(
            			'uuid'=>$flow_inst_info['uuid'],
            			'flow_instance_status'=>SysAuditFlowInstance::FLOW_STATUS_REFUSED
            	);
            	$instance->saveOrUpdate();
            	$redirect_rule = $node_info["redirect_rule"];
            	//获取下一节点角色
            	$next_node_role_uuids = null;
            	$users = $flow_inst_info['create_user_id'];
            }
            else
            {
            	//有下一节点，插入
            	//下一节点
				$flow_node_instance = new SysAuditLog();
				$flow_node_instance->params = array(
					'instance_uuid'=>$flow_inst_info['uuid'],
					'node_uuid'=>$n_node_info['uuid'],
					'deal_result'=>SysAuditLog::CODE_NODE_DEAL_RESULT_WAITING,
					'create_user_id'=>$userInfo['user_id']??0,
					'create_user_name'=>$userInfo['username']
				);
				$node_uuid = $flow_node_instance->saveOrUpdate();
				
				$instance = new SysAuditFlowInstance();
				$instance->params = array(
						'uuid'=>$flow_inst_info['uuid'],
						'node_instance_uuid'=>$node_uuid
				);
				$instance->saveOrUpdate();
				
				$flow_node_instance->saveOrUpdate();
				$redirect_rule = $n_node_info["redirect_rule"];
				//获取下一节点角色
				$next_node_role_uuids = SysFlowNodeAuth::getRolesByNodeId($n_node_info['uuid']);
            }
            $ret = array(
            		"uuid"=>$flow_inst_info["uuid"]
            );
            
            
            $obj->commit();
       	}
       	catch(Exception $e)
       	{
            $obj->rollback();
//             throw $e;
            throw new Exception("审批流调用失败|".$e->getMessage(),ErrMsg::RET_CODE_SERVICE_FAIL);
       	}
       	if(isset($flow_info['server_name'])&&!empty($flow_info['server_name'])){
       		if(!empty($next_node_role_uuids)){
       			$r = explode(',',$next_node_role_uuids);
       			$u = new SysUser();
       			$users = $u->getUserIdForMainUuidRoleId($flow_inst_info['main_body_uuid'] , $r);
       		}
       		 
       		//调用接口通知。
       		$request_array = array(
       				'sessionToken'=>$this->m_request['sessionToken'],
       				'flow_code'=>$this->m_request['flow_code'],
       				'node_code'=>$node_info['node_code'],
       				'instance_id'=>$this->m_request['instance_id'],
       				'uuid'=>$flow_inst_info['uuid'],
       				'optor'=>$userInfo['username'],
       				'optor_id'=>$userInfo['user_id'],
       				'status'=>$status,
       				'next_node_role_uuids'=>$next_node_role_uuids,
       				'next_node_users'=>$users,
       				'node_status'=>$node_status,
       				'create_user_name'=>$flow_inst_info['create_user_name'],
       				'create_user_id'=>$flow_inst_info['create_user_id']
       		);
       		if(isset($this->m_request['info'])){
       			$request_array['msg'] = $this->m_request['info'];
       		}
       	
       		if(isset($this->m_request['params'])){
       			$request_array['params'] = $this->m_request['params'];
       		}
       	
       		$recall_ret = JmfUtil::call_Jmf_consumer($flow_info['server_name'],$request_array,10);
       		if(empty($recall_ret)||!isset($recall_ret['code'])){
       			\CommonLog::instance()->getDefaultLogger()
					->error('审批流回调接口异常|server_name'.$flow_info['server_name'].'in:'.json_encode($request_array).'|out:'.json_encode($recall_ret));
       		}
       		if($recall_ret['code']!='0'){
       			\CommonLog::instance()->getDefaultLogger()
					->error('审批流回调接口异常|server_name'.$flow_info['server_name'].'in:'.json_encode($request_array).'|out:'.json_encode($recall_ret));
       			SysAuditLog::flowDataRollback($flow_inst_info['uuid'], $this->m_request['approve_type']);
       			throw new Exception("审批流调用失败|".$recall_ret['msg'],ErrMsg::RET_CODE_SERVICE_FAIL);
       		}
       		if(isset($recall_ret['data'])&&!empty($recall_ret['data'])){
       			$ret['data'] = $recall_ret['data'];
       		}
       	}

       	$this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
}
