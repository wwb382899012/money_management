<?php
/**
 * 理财产品详情
 */
use money\service\BaseService;
use money\model\MoneyPlan;
use money\logic\CommonLogic;

class DetailPlan extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'plan_uuid' => 'require',
    ];

    public function exec(){
        $uuid = $this->m_request['plan_uuid'];
        $sessionToken = $this->m_request['sessionToken'];

        $commonLogic = new CommonLogic();
        $db = new MoneyPlan();
        $data = $commonLogic->uuidPriv($uuid, $sessionToken, $db);

        $data = $this->detailToShow($data);
        $data['node_list'] = [];
        if($data['real_pay_type'] == 1){
			$data['node_list'] = $this->auditLog($uuid, AUDIT_FLOW_CODE_FINANCIAL_PAY_TYPE_1, $sessionToken);
        }else if($data['real_pay_type'] == 2){
            $data['node_list'] = $this->auditLog($uuid, AUDIT_FLOW_CODE_FINANCIAL_PAY_TYPE_2, $sessionToken);
        }

        $log = $this->auditLog($uuid, REDEM_AUDIT_FLOW_CODE, $sessionToken);
        !empty($log) && $data['node_list'] = array_merge($data['node_list'], $log);

        $this->packRet(ErrMsg::RET_CODE_SUCCESS, $data);
    }  

    protected function detailToShow($data){
        $keys = [
            'money_manager_product_uuid', 'money_manager_product_name', 'money_manager_plan_num','plan_main_body_uuid',
            'plan_main_body_name', 'pay_bank_uuid', 'pay_bank_account', 'pay_bank_name', 'currency', 'amount', 'rate_start_date', 'rate_over_date',
            'forecast_annual_income_rate', 'forecast_interest', 'plan_status', 'pay_status', 'is_pay_off', 'create_time',
            'term_type', 'cash_flow','real_pay_type','audit_step','sys_water','require_pay_datetime','bank_water','bank_img_file_uuid',
            'need_ticket_back',
        ];
        $cashKey = [
            'amount', 'cash_flow_type', 'change_amount', 'create_time', 
            'repay_date', 'status', 'uuid'
        ];
        $result = [];
        $nodeUuids = [];
        foreach ($data as $key => $value) {
            if(in_array($key, $keys)){
                $result[$key] = $value;
            }
            $result['plan_uuid'] = $data['uuid'];
        }
        return $result;
    }

    /**
     * 审核日志
     */
    protected function auditLog($uuid, $node,$sessionToken){
        $flow = [
            'flow_code' => $node,
            'instance_id' => $uuid,
            'sessionToken' => $sessionToken
        ];
        $ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.DetailList', $flow);
        if(!isset($ret['code']) || $ret['code'] != '0'){
            return [];
        }
        return $ret['data'][0]['node_list'] ?? [];
    }
}