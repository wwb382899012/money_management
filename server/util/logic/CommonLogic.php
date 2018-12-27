<?php
/**
 * 通用逻辑
 */

namespace money\logic;

use money\model\BaseModel;
use money\model\MoneyPlan;

class CommonLogic{
    /**
     * 根据主键获取审核数据
     */
    public function getAuditUuid($uuid){
        $data['instance_id'] = $uuid;
        $data['sessionToken'] = 'test';
        $data['status'] = 2;
        $auditData = \JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList', $data);
        if(!isset($auditData['code']) || $auditData['code']!=0 || empty($auditData['data'])){
            return null;
        }
        $result = [];
        foreach($auditData['data'][0]['node_list'] as $row){
            $result[] = ['name'=>$row['optor'], 'time'=>$row['create_time']];
        }
        return $result;
    }

    public function getAuditLog($instanceUuid, array $flowCode = []){
        $model = new BaseModel();
        $flowData = $model->getList(['flow_code' => $flowCode], 'uuid', null, null, null, 'm_sys_audit_flow');
        
        $where = ['i.instance_id' => $instanceUuid, 'i.flow_uuid' => array_column($flowData, 'uuid'), 'l.deal_result' => [2,1]];
        try {
            $list = $model->table('m_sys_audit_instance i')->field('l.deal_user_id,l.create_user_id,l.create_user_name,l.deal_user_name, max(l.create_time)create_time, max(l.update_time)update_time')
                ->leftJoin('m_sys_audit_log l', 'i.uuid=l.instance_uuid')
                ->where($where)->group('l.node_uuid')->order('l.create_time asc,l.update_time asc')->select()->toArray();
        } catch (\Exception $e) {
            $list = [];
        }
        return $list;
    }

    /**
     * 判断理财权限
     */
    public function uuidPriv($uuid, $sessionToken, $db=null){
        if(!$db){
            $db = new MoneyPlan();
        }
        $data = $db->detail($uuid);
        if(!$data){
            throw new \Exception('理财计划不存在', \ErrMsg::RET_CODE_SERVICE_FAIL);
        }

        $ret = \JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.user.PrivData', ['sessionToken'=>$sessionToken, 'tablename'=>'m_money_manager_plan']);
        if(!isset($ret['code']) || $ret['code'] != '0' || !isset($ret['data']['plan_main_body_uuid'])){
            throw new \Exception('获取数据权限数据失败', \ErrMsg::RET_CODE_SERVICE_FAIL);
        }

        if(!in_array($data['plan_main_body_uuid'], $ret['data']['plan_main_body_uuid'])){
            throw new \Exception('无数据操作权限', \ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        return $data;
    }

    /**
     * 获取理财计划的当前审核节点状态
     * @return ['uuid'=>audit_step]  audit_step 1未有审核状态 2资金专员审核 3权限人审核 4上传流水操作
     */
    public function getFinancialNode($uuid, $node,$sessionToken){
        $flow = [
            'flow_code' => $node,
            'instance_id' => $uuid,
            'sessionToken' => $sessionToken,
        ];
        $ret = \JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList', $flow);
        if(!isset($ret['code']) || $ret['code'] != '0' ){
            throw new \Exception('获取流程数据失败'.$ret['msg'], \ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        if(empty($ret['data'])){
            return 1;
        }

        foreach($ret['data'][0]['node_list'] as $list){
            if($list['is_current_node'] == 1 && $list['node_code']==AUDIT_NODE_PAY_TYPE_1_NO_1){
                return 1;
            }else if($list['is_current_node'] == 1 && $list['node_code']==AUDIT_NODE_PAY_TYPE_1_NO_2){
                return 2;
            }else if($list['is_current_node'] == 1 && $list['node_code']==AUDIT_NODE_PAY_TYPE_1_NO_3){
                return 3;
            }else if($list['is_current_node'] == 1 && $list['node_code']==AUDIT_NODE_PAY_TYPE_1_NO_4){
                return 4;
            }
        }
        return 1;
    }
}