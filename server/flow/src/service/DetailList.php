<?php
/**
*	审批流详情页
*	@author sun
*	@since 2018-03-21
*/
use money\service\BaseService;
use money\base\ParamsUtil;
use money\model\SysAuditFlow;

class DetailList extends BaseService
{
    protected $rule = [
        'sessionToken' => 'require',
    ];

	public function exec()
	{
// 		//参数验证
// 		if(array_key_exists('instance_id',$this->m_request)&&!array_key_exists('flow_code',$this->m_request))
// 		{
// 			throw new \Exception("传入实例id时，流程编码为必填",ErrMsg::RET_CODE_MISS_PARAMS);
// 		}
		
		//用户详情获取
// 		$sessionInfo = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.layer.SessionGet', ['sessionToken'=>$this->m_request['sessionToken']]);
//         if(!isset($sessionInfo['code']) || $sessionInfo['code'] != '0' || !isset($sessionInfo['data']['user_id'])){
//             $code = isset($sessionInfo['code']) ? $sessionInfo['code'] : ErrMsg::RET_CODE_SERVICE_FAIL;
//             $msg = isset($sessionInfo['msg']) ? $sessionInfo['msg'] : '获取会话信息失败';
//             throw new \Exception($msg, $code);
//         }
		
// 		$userInfo = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.user.UserDetail'
// 				, ['user_id'=>$sessionInfo['data']['user_id'],'sessionToken'=>$this->m_request['sessionToken']]);
// 		if(!isset($userInfo['code'])||!isset($userInfo['data'])||$userInfo['code']!=0){
// 			throw new \Exception('获取用户信息失败',ErrMsg::RET_CODE_SERVICE_FAIL);
// 		}else if(!$userInfo['data']['user_id']){
// 			throw new \Exception('获取用户信息失败',ErrMsg::RET_CODE_SERVICE_FAIL);
// 		}
// 		$role = isset($userInfo['data']['role'])?$userInfo['data']['role']:null;
// 		if(!$role||count($role)==0){
// 			throw new \Exception('当前用户没有角色，无法访问数据',ErrMsg::RET_CODE_SERVICE_FAIL);
// 		}
// 		$role_uuids = ParamsUtil::getArrayByKey($role ,'uuid');
		
// 		$main_body = isset($userInfo['data']['main_body'])?$userInfo['data']['main_body']:null;
// 		if(!$main_body||count($main_body)==0){
// 			throw new \Exception('当前用户没有设置主体，无法访问数据',ErrMsg::RET_CODE_SERVICE_FAIL);
// 		}
// 		$main_body_uuids = ParamsUtil::getArrayByKey($main_body ,'uuid');
		
// 		if(count($main_body_uuids)==0){
// 			$this->packRet(ErrMsg::RET_CODE_SUCCESS, ErrMsg::RET_CODE_DATA_NOT_EXISTS);
// 			return;
// 		}
		
		$obj = new SysAuditFlow();

        $flow_instance_status = ParamsUtil::getDataByArray($this->m_request,'status');
        if (isset($flow_instance_status)) {
            $whereSql = " where flow_instance_status = '". $flow_instance_status ."' ";
        } else {
            $whereSql = " where 1 ";
        }
    	$instance_id = ParamsUtil::getDataByArray($this->m_request,'instance_id');
    	if(isset($instance_id)){
    		$id_array = explode(',',$instance_id);
    		
    		$whereSql .= ' and instance_id in ("'.implode('","',$id_array).'")';
    	}
    	
    	$flow_code = ParamsUtil::getDataByArray($this->m_request,'flow_code');
    	if(isset($flow_code)){
    		$flow_code_array = explode(',',$flow_code);
    	
    		$whereSql .= ' and flow_code in ("'.implode('","',$flow_code_array).'")';
    	}
        
        $str = '';
    	if(array_key_exists('node_code',$this->m_request))
        {
        	$str=" and n.node_code = '".$this->m_request['node_code']."'";
        }
        
    	$whereSql.= " and exists " 
    		   ." (select 1 from m_sys_audit_log l "
    		   ." join m_sys_flow_node n on n.uuid = l.node_uuid "
//     		   ." join m_sys_flow_node_auth a on a.node_uuid = n.uuid "
    		   ." where n.flow_uuid = f.uuid $str )";
//     		   and role_id in('".implode("','", $role_uuids)."')  "
//     		   ." and main_body_uuid in ('".implode("','",$main_body_uuids)."')";
		
		$pageSize = isset($this->m_request['limit'])?$this->m_request['limit']:50;
		$page = isset($this->m_request['page'])?$this->m_request['page']:1;

    	if($pageSize&&$pageSize<0)
    	{
	    	$limit = '';
		}
		else
		{
			if(!$page)
				$page = 1;
			if(!$pageSize)
				$pageSize = 50;
			$begin = ($page - 1) * $pageSize;
			$limit = " limit $begin , $pageSize ";
		}
		$col = " i.uuid, f.flow_code , i.instance_id , i.flow_instance_status status , i.create_time , i.update_time";
        $sql = "select $col from m_sys_audit_instance i join m_sys_audit_flow f on f.uuid = i.flow_uuid ".$whereSql." order by create_time desc ".$limit;
        $res = $obj->query($sql);

        $array = array();
        foreach($res as $row)
        {
        	$array[] = $row['uuid'];
        }
        $sql = "select n.flow_uuid,n.uuid node_id,n.node_code,n.node_desc,
        			l.deal_result node_status, l.create_user_id creator , cu.name creator_name,
        			l.deal_user_id optor , u.name optor_name,
        			case l.deal_result when 1 then 1 else 2 end is_current_node ,
        			l.create_time,l.update_time ,l.instance_uuid,deal_remark,i.instance_id
        		from m_sys_audit_log l join m_sys_flow_node n 
        			on n.uuid = l.node_uuid
        			left join m_sys_user u on u.user_id = l.deal_user_id
        		   left join m_sys_user cu on cu.user_id = l.create_user_id
        			join m_sys_audit_instance i on i.uuid = l.instance_uuid
        		where i.uuid in ('".implode("','", $array)."') order by l.update_time";
        $node_res = $obj->query($sql);

        $map = array();
        foreach($node_res as $row)
        {
        	if(!isset($map[$row['instance_id']]))
        	{
        		$map[$row['instance_id']] = array();
        	}
        	$instance_uuid = $row['instance_id'];
        	unset($row['instance_id']);
        	$map[$instance_uuid][] = $row;
        }
        $ret = array();
        foreach($res as $row)
        {
        	$row['node_list'] = $map[$row['instance_id']];
        	unset($row['uuid']);
        	$ret[] = $row;
        }
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $ret);
	}
}