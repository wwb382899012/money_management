<?php
/**
 * 新增/编辑理财计划
 */
use money\service\BaseService;
use money\model\MoneyProduct;
use money\model\MoneyPlan;
use money\model\MainBody;
use money\model\BankAccount;
use money\logic\EventProvider;

class AddPlan extends BaseService{

    protected $rule = [
        'sessionToken' => 'require',
        'money_manager_product_uuid' => 'require',
        'plan_main_body_uuid' => 'require',
        'pay_bank_uuid' => 'require',
        'pay_bank_account' => 'require',
        'pay_bank_name' => 'require',
        'real_pay_type' => 'require|in:1,2',
        'term_type' => 'require|in:1,2',
        'amount' => 'require|number',
        'currency' => 'require',
        'rate_start_date' => 'require|date',
        'rate_over_date' => 'date',
        'forecast_annual_income_rate' => 'require|number',
        'forecast_interest' => 'number',
        'cash_flow' => 'require|array',
        'if_audit' => 'integer|in:1,2',
    ];

    public function exec(){
        $sessionToken = $this->m_request['sessionToken'];
        $prdDb = new MoneyProduct();
        $prdData = $prdDb->detail($this->m_request['money_manager_product_uuid']);
        if(!$prdData || $prdData['status'] != 1){
            throw new \Exception('理财产品不存在或已注销', ErrMsg::RET_CODE_SERVICE_FAIL);
        }
        $sessionToken = $this->m_request['sessionToken'];
        $sessionInfo = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.layer.SessionGet', ['sessionToken'=>$sessionToken]);
        if(!isset($sessionInfo['code']) || $sessionInfo['code'] != '0' || !isset($sessionInfo['data']['user_id'])){
            $code = isset($sessionInfo['code']) ? $sessionInfo['code'] : ErrMsg::RET_CODE_SERVICE_FAIL;
            $msg = isset($sessionInfo['msg']) ? $sessionInfo['msg'] : '获取会话信息失败';
            throw new \Exception($msg, $code);
        }
        $planData = $this->planParamCheck($sessionInfo['data']);
        $cashData = $this->cashParamCheck($sessionInfo['data']);

        $planDb = new MoneyPlan();

        try{
            $planUuid = $planDb->savePlanAndCash($planData, $cashData);

            // 推送审核
            if($this->m_request['if_audit'] == 1){
                $flowData = [
                    'flow_code'=>$planData['real_pay_type']==1?AUDIT_FLOW_CODE_FINANCIAL_PAY_TYPE_1:AUDIT_FLOW_CODE_FINANCIAL_PAY_TYPE_2, 
                    'instance_id'=>$planUuid, 
                    'sessionToken' => $this->m_request['sessionToken'],
                    'main_body_uuid' => $planData['plan_main_body_uuid'],
                    'params' => []
                ];
                $ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.flow.Start', $flowData);
                if(!isset($ret['code']) || $ret['code'] != '0'){
                    throw new \Exception('提交审核失败:'.$ret['msg'], ErrMsg::RET_CODE_SERVICE_FAIL);
                }
            }
            $this->packRet(ErrMsg::RET_CODE_SUCCESS, ['uuid'=>$planUuid]);
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     *  产品参数检测
     */
    protected function planParamCheck($sessionData){
        $data = [
            'money_manager_product_uuid' => $this->m_request['money_manager_product_uuid'],
            'plan_main_body_uuid' => $this->m_request['plan_main_body_uuid'],
            'pay_bank_name' => $this->m_request['pay_bank_name'],
            'pay_bank_account' => $this->m_request['pay_bank_account'],
            'pay_bank_uuid' => $this->m_request['pay_bank_uuid'],
            'real_pay_type' => $this->m_request['real_pay_type'],
            'term_type' => $this->m_request['term_type'],
            'amount' => $this->m_request['amount'],
            'currency' => $this->m_request['currency'],
            'rate_start_date' => $this->m_request['rate_start_date'],
            'rate_over_date' => !empty($this->m_request['rate_over_date']) ? $this->m_request['rate_over_date'] : null,
            'forecast_annual_income_rate' => $this->m_request['forecast_annual_income_rate'],
            'forecast_interest' => $this->m_request['forecast_interest'] ?? 0,
        ];
        //封闭式有rate_over_date参数
        if ($data['term_type'] == 2 && strtotime($data['rate_start_date']) > strtotime($data['rate_over_date'])) {
            throw new \Exception('起息日必须小于等于投资产品到期日', ErrMsg::RET_CODE_GENVERIFY_TYPE_ERROR);
        }
        $data['create_user_id'] = $sessionData['user_id'];
        $data['create_user_name'] = $sessionData['name'];
        $data['create_time'] = date('Y-m-d H:i:s');
        
        $mainBody = MainBody::getDataById($data['plan_main_body_uuid']);
        $data['money_manager_plan_num'] = MoneyPlan::getOrderNum($mainBody['short_code']);

        // 获取主体名称
        $list = MainBody::changeUuidToName([$data] , 'plan_main_body_uuid' , 'plan_main_body_name');
        $data = $list[0];

        // 检查银行信息
        $oBankAmount = new BankAccount();
        if(isset($data['pay_bank_account'])){
            $pay_bank_account = $oBankAmount->getByAccount($data['pay_bank_account']);
            if(empty($pay_bank_account)){
                throw new Exception('付款账户不存在',ErrMsg::RET_CODE_DATA_NOT_EXISTS);
            }
            if(!isset($pay_bank_account['main_body_uuid'])){
                throw new Exception('付款账户不在付款主体下',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
            }
            if($pay_bank_account['main_body_uuid']!=$data['plan_main_body_uuid']){
                throw new Exception('付款账户不在付款主体下',ErrMsg::RET_CODE_DATA_VALIDATE_ERROR);
            }
        }

        // 检查主体权限
        $ret = JmfUtil::call_Jmf_consumer('com.jyblife.logic.bg.user.PrivData', ['sessionToken'=>$this->m_request['sessionToken'], 'tablename'=>'m_money_manager_plan']);
        if(!isset($ret['code']) || $ret['code'] != '0'){
            throw new \Exception('获取数据权限数据失败', ErrMsg::RET_CODE_SERVICE_FAIL);
        }

        if(!in_array($this->m_request['plan_main_body_uuid'], array_values($ret['data']['plan_main_body_uuid']))){
            throw new \Exception('无主体权限', ErrMsg::RET_CODE_SERVICE_FAIL);
        }

        return $data;
    }

    /**
     * 现金流数据检测
     */
    protected function cashParamCheck($sessionData){
        $data = $this->m_request['cash_flow'];
        $keys = ['repay_date', 'cash_flow_type', 'amount', 'change_amount'];
        $result = [];
        if(count($data) < 1){
            throw new \Exception('cash_flow参数位数错误', ErrMsg::RET_CODE_GENVERIFY_TYPE_ERROR);
        }
        $flag = false;
        $investmentAmount = $this->m_request['amount'];
        $redemptionAmount = 0;
        foreach($data as $k=>$row){
            if(!empty(array_diff($keys, array_keys($row)))){
                throw new \Exception('cash_flow参数错误', ErrMsg::RET_CODE_GENVERIFY_TYPE_ERROR);
            }
            if($row['cash_flow_type'] == 1){
                $flag = true;
                if ($row['amount'] != $this->m_request['amount']) {
                    throw new \Exception('支付金额不一致', ErrMsg::RET_CODE_GENVERIFY_TYPE_ERROR);
                }
            } elseif ($row['cash_flow_type'] == 2) {
                //赎回本金
                if (!isset($row['is_delete']) || $row['is_delete'] == 1) {
                    $redemptionAmount += $row['amount'] - $row['change_amount'];
                }
            } elseif ($row['cash_flow_type'] == 3) {
                //赎回利息
            } else {
                throw new \Exception('cash_flow_type参数错误', ErrMsg::RET_CODE_GENVERIFY_TYPE_ERROR);
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
                'create_user_id' => $sessionData['user_id'],
                'create_user_name' => $sessionData['name'],
            ];
        }
        if(!$flag){
            throw new \Exception('现金流中缺乏本金', ErrMsg::RET_CODE_GENVERIFY_TYPE_ERROR);
        }
        if ($investmentAmount < $redemptionAmount) {
            throw new \Exception("赎回金额大于支付金额", ErrMsg::RET_CODE_GENVERIFY_TYPE_ERROR);
        }
        return $result;
    }
}