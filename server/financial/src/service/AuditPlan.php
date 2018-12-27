<?php
/**
 * 理财计划审核
 */

use money\model\ReportFullTrade;
use money\model\SysAuditLog;
use money\service\BaseService;
use money\model\MoneyPlan;
use money\logic\CommonLogic;

class AuditPlan extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'plan_uuid' => 'require',
        'type' => 'require|in:start,audit',
        'approve_type' => 'integer|in:1,2,3|requireIf:type,audit',
    ];

    public function exec(){
        $uuid = $this->m_request['plan_uuid'];
        $sessionToken = $this->m_request['sessionToken'];
        $type = $this->m_request['type'];
        $params = [];

        $commonLog = new CommonLogic();
        $detail = $commonLog->uuidPriv($uuid, $sessionToken);
        if($type == 'start'){
            $flowData = [
                'flow_code'=>$detail['real_pay_type']==1?AUDIT_FLOW_CODE_FINANCIAL_PAY_TYPE_1:AUDIT_FLOW_CODE_FINANCIAL_PAY_TYPE_2, 
                'instance_id'=>$uuid, 
                'sessionToken' => $sessionToken,
                'main_body_uuid' => $detail['plan_main_body_uuid'],
                'params' => $params
            ];
            $ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Start', $flowData);
            if(!isset($ret['code']) || $ret['code'] != '0'){
                throw new \Exception('提交审核失败:'.$ret['msg'], ErrMsg::RET_CODE_SERVICE_FAIL);
            }                                    
        }else{
            $approve_type = isset($this->m_request['approve_type']) ? $this->m_request['approve_type']:null;
            if($detail['need_ticket_back'] == 1){
                if(!isset($this->m_request['bank_water']) || !isset($this->m_request['bank_img_file_uuid'])){
                    throw new \Exception('缺少参数bank_water_no|bank_img_file_uuid', ErrMsg::RET_CODE_GENVERIFY_TYPE_ERROR);
                }
                $params['bank_water'] = $this->m_request['bank_water'];
                $params['bank_img_file_uuid'] = $this->m_request['bank_img_file_uuid'];
            }
            $flowData = [
                'flow_code'=>$detail['real_pay_type']==1?AUDIT_FLOW_CODE_FINANCIAL_PAY_TYPE_1:AUDIT_FLOW_CODE_FINANCIAL_PAY_TYPE_2, 
                'instance_id'=>$uuid, 
                'sessionToken' => $sessionToken,
                'params' => $params,
                'approve_type' => $approve_type,
                'info' => isset($this->m_request['info'])?$this->m_request['info']:''
            ];
            $ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Approve', $flowData);
            if(!isset($ret['code']) || $ret['code'] != '0'){
                throw new \Exception('提交审核失败:'.$ret['msg'], ErrMsg::RET_CODE_SERVICE_FAIL);
            }
            if ($approve_type == SysAuditLog::CODE_NODE_APPROVED && $detail['plan_status'] == MoneyPlan::PLAN_STATUS_OPTED) {
                //生成全量报表
                $obj = new ReportFullTrade();
                $obj->saveData(4, $detail['money_manager_plan_num']);
            }
        }
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, []);
    }
}