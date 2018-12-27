<?php
/**
 * 更新赎回流水
 */
use money\service\BaseService;
use money\model\MoneyPlan;
use money\model\EodTradeDb;
use money\logic\CommonLogic;

class SaveRedemption extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'plan_uuid' => 'require',
        'cash_flow' => 'require|array',
    ];

    public function exec(){
        $plan_uuid = $this->m_request['plan_uuid'];
        $cash_flow = $this->m_request['cash_flow'];
        $sessionToken = $this->m_request['sessionToken'];

        $db = new MoneyPlan();
        $commonLogic = new CommonLogic();
        $planDetail = $commonLogic->uuidPriv($plan_uuid, $sessionToken, $db);

        $sessionInfo = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.layer.SessionGet', ['sessionToken'=>$sessionToken]);
        if(!isset($sessionInfo['code']) || $sessionInfo['code'] != '0' || !isset($sessionInfo['data']['user_id'])){
            $code = isset($sessionInfo['code']) ? $sessionInfo['code'] : ErrMsg::RET_CODE_SERVICE_FAIL;
            $msg = isset($sessionInfo['msg']) ? $sessionInfo['msg'] : '获取会话信息失败';
            throw new \Exception($msg, $code);
        }

        $keys = ['repay_date', 'cash_flow_type', 'amount', 'change_amount'];
        $result = [];
        $investmentAmount = $planDetail['amount'];
        $redemptionAmount = 0;
        foreach($cash_flow as $k=>$row){
            if(!empty(array_diff($keys, array_keys($row)))){
                throw new \Exception('cash_flow参数错误', ErrMsg::RET_CODE_GENVERIFY_TYPE_ERROR);
            }
            !isset($row['is_delete']) && $row['is_delete'] = 1;
            if(isset($row['uuid'])){
                $flag = false;
                foreach($planDetail['cash_flow'] as $cr){
                    if($cr['uuid'] == $row['uuid']){
                        $row = array_merge($cr, $row);
                        if ($row['is_delete'] == 2 || in_array($cr['status'], [MoneyPlan::PLAN_STATUS_SAVED, MoneyPlan::PLAN_STATUS_CHECK_REJECT])) {
                            $flag = true;
                        }
                        break;
                    }
                }
                if ($row['cash_flow_type'] == 2 && $row['is_delete'] == 1 && !in_array($row['status'], [MoneyPlan::PLAN_STATUS_CHECK_REJECT])) {
                    $redemptionAmount += $row['amount'] - $row['change_amount'];
                }
                if(!$flag){
                    continue;
                }
            } else {
                if ($row['cash_flow_type'] == 2 && $row['is_delete'] == 1) {
                    $redemptionAmount += $row['amount'] - $row['change_amount'];
                }
            }
            $result[$k] = [
                'uuid' => $row['uuid'] ?? null,
                'repay_date' => $row['repay_date'],
                'cash_flow_type' => $row['cash_flow_type'],
                'amount' => $row['amount'],
                'change_amount' => $row['change_amount'] ?? 0,
                'info' => $row['info'] ?? '',
                'index' => $row['index'] ?? 0,
                'is_delete' => $row['is_delete'] ?? 1,
                'status' => $row['status'] ?? MoneyPlan::PLAN_STATUS_SAVED,
                'create_user_id' => $row['create_user_id'] ?? $sessionInfo['data']['user_id'],
                'create_user_name' => $row['create_user_name'] ?? $sessionInfo['data']['name'],
                'audit' => $row['audit'] ?? 2,
            ];
        }
        if ($investmentAmount < $redemptionAmount) {
            throw new \Exception("赎回金额大于支付金额", ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        if(!$db->saveCashFlow($result, $plan_uuid)){
            throw new \Exception("现金流数据保存失败", ErrMsg::RET_CODE_SERVICE_FAIL);
        }

        //开放式理财走审批流
        if ($planDetail['term_type'] == 1) {
            foreach ($result as $row) {
                if ($row['audit'] == 1) {
                    $auditCashFlow[] = $row;
                }
            }
        }
        if(!empty($auditCashFlow)){
	        $flow['flow_code'] = REDEM_AUDIT_FLOW_CODE;
	        $flow['instance_id'] = $this->m_request['plan_uuid'];
	        $flow['main_body_uuid'] = $planDetail['plan_main_body_uuid'];
	        $flow['sessionToken'] = $sessionToken;
            $flow['params'] = ['cash_flow' => $auditCashFlow];
	        
	        $ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Start', $flow, 5);
	        if(!isset($ret['code']) || $ret['code'] != '0'){
	        	throw new \Exception('提交审核失败:'.$ret['msg'], ErrMsg::RET_CODE_SERVICE_FAIL);
	        }
        }
        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $result);
    }
}