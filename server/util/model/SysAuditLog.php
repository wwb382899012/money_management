<?php
/**
*	流程节点实例表
*	@author sun
*	@since 2018-03-20
*/

namespace money\model;

class SysAuditLog extends BaseModel
{
	const CODE_NODE_DEAL_RESULT_WAITING = 1;
	const CODE_NODE_DEAL_RESULT_APPROVED = 2;
	const CODE_NODE_DEAL_RESULT_REJECT = 3;
	const CODE_NODE_DEAL_RESULT_REFUSE = 4;
	
	const CODE_NODE_APPROVED = 1;
	const CODE_NODE_REJECT = 2;
	const CODE_NODE_REFUSED = 3;
	const CODE_NODE_STOP = 4;
	public $table = 'm_sys_audit_log';

		//获取流程当前节点信息
		public static function loadCurNodeInfo($flow_instance_uuid)
	{
		$flow_instance_info = SysAuditFlowInstance::getDataById($flow_instance_uuid);
		return self::getDataById($flow_instance_info['node_instance_uuid']);
	}

	/**
	 * approve_type 1:审批通过 2 审批驳回 3 审批拒绝
	 * @param unknown $uuid
	 * @param unknown $approve_type
	 */
	public static function flowDataRollback($uuid , $approve_type){
		/**
		1、如果是审批通过：
				如果是结束节点，则只更新该节点数据日志状态为审批中，恢复主数据状态为审批中。
				如果是普通节点，则删除该节点数据日志，恢复上一节点日志状态，更新主数据当前节点为上一节点。
				如果上一节点是发起节点，需要系统自动删除该条数据和日志数据。
		2、如果是审批驳回：
				删除该节点数据日志，恢复上一节点日志状态，更新主数据当前节点为上一节点，更新主数据状态为审批中
		3、如果是审批拒绝：
				则更新该节点状态为审批中，更新主数据状态为审批中。
		4、如果是审批终结（stop）：
				则更新该节点状态为审批中，更新主数据状态为审批中。
		 */
		$flow_inst_info = SysAuditFlowInstance::getDataById($uuid);
		if(empty($flow_inst_info)){
			return;
		}
		$node_inst_info = SysAuditLog::getDataById($flow_inst_info['node_instance_uuid']);
		if(empty($node_inst_info)){
			return;
		}
		$node_info = SysFlowNode::getDataById($node_inst_info['node_uuid']);
		if(empty($node_info)){
				
			return;
		}
		$obj = new SysAuditLog();
		try{
			$obj->startTrans();
			if($approve_type==SysAuditLog::CODE_NODE_APPROVED){
				switch($node_info['node_type']){
					//审批通过，当前节点不可能是开始节点

					//如果是普通节点，则删除该节点数据日志，恢复上一节点日志状态，更新主数据当前节点为上一节点。如果上一节点是开始节点，删除主数据，删除上一节点数据
					case 2:
						$log = new SysAuditLog();
						$sql = "select * from m_sys_audit_log l join m_sys_flow_node n on n.uuid = l.node_uuid where instance_uuid = '".$flow_inst_info['uuid']."' order by l.create_time,n.node_sort desc limit 2";
						$logs = $log->query($sql);
						$per_log_info = $logs[1];
						
						$log->where("uuid='".$node_inst_info['uuid']."'")->delete();
						
						$per_node_info = SysFlowNode::getDataById($per_log_info['node_uuid']);
						if($per_node_info['node_type']==1){
							$log->delete($per_log_info['uuid']);
							$instance = new SysAuditFlowInstance();
							$instance->where("uuid='".$flow_inst_info['uuid']."'")->delete();
						}else{
							$params = [
								'uuid'=>$per_log_info['uuid'],
								'deal_result'=>SysAuditLog::CODE_NODE_DEAL_RESULT_WAITING
							];
							$log->params = $params;
							$log->saveOrUpdate();
							$params = [
								'uuid'=>$flow_inst_info['uuid'],
								'node_instance_uuid'=>$per_log_info['uuid'],
								'flow_instance_status'=>1
							];
							$instance = new SysAuditFlowInstance();
							$instance->params = $params;
							$instance->saveOrUpdate();
						}
					//如果是结束节点，则只更新该节点数据日志状态为审批中，恢复主数据状态为审批中。
					case 3:
						$log = new SysAuditLog();
						$params = [
							'uuid'=>$node_inst_info['uuid'],
							'deal_result'=>SysAuditLog::CODE_NODE_DEAL_RESULT_WAITING
						];
						$log->params = $params;
						$log->saveOrUpdate();
						$params = [
							'uuid'=>$flow_inst_info['uuid'],
							'flow_instance_status'=>1
						];
						$instance = new SysAuditFlowInstance();
						$instance->params = $params;
						$instance->saveOrUpdate();
				}
				
			}else if($approve_type==SysAuditLog::CODE_NODE_REJECT){
				//删除该节点数据日志，恢复上一节点日志状态，更新主数据当前节点为上一节点，更新主数据状态为审批中
				$log = new SysAuditLog();
				$sql = "select * from m_sys_audit_log l join m_sys_flow_node n on n.uuid = l.node_uuid where instance_uuid = '".$flow_inst_info['uuid']."' order by l.create_time,n.node_sort desc limit 2";
				$logs = $log->query($sql);
				$per_log_info = $logs[1];
					
				$log->delete($node_inst_info['uuid']);
				$params = [
				'uuid'=>$per_log_info['uuid'],
				'deal_result'=>SysAuditLog::CODE_NODE_DEAL_RESULT_WAITING
				];
				$log->params = $params;
				$log->saveOrUpdate();
				$params = [
					'uuid'=>$flow_inst_info['uuid'],
					'node_instance_uuid'=>$per_log_info['uuid'],
					'flow_instance_status'=>1
				];
				$instance = new SysAuditFlowInstance();
				$instance->params = $params;
				$instance->saveOrUpdate();
			}else if($approve_type==SysAuditLog::CODE_NODE_REFUSED||$approve_type==SysAuditLog::CODE_NODE_REFUSED){
				//则更新该节点状态为审批中，更新主数据状态为审批中。
				$log = new SysAuditLog();
				$params = [
				'uuid'=>$node_inst_info['uuid'],
				'deal_result'=>SysAuditLog::CODE_NODE_DEAL_RESULT_WAITING
				];
				$log->params = $params;
				$log->saveOrUpdate();
				$params = [
				'uuid'=>$flow_inst_info['uuid'],
				'flow_instance_status'=>1
				];
				$instance = new SysAuditFlowInstance();
				$instance->params = $params;
				$instance->saveOrUpdate();
			}
			$obj->commit();
		}catch (\Exception $e){
			$obj->rollback();
			throw new \Exception("审批流调用失败|".$e->getMessage(),\ErrMsg::RET_CODE_SERVICE_FAIL);
		}
	}
}
