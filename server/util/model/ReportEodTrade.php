<?php

/**
 * Class ReportFullTrade
 */
namespace money\model;
use money\base\MapUtil;
use money\logic\CommonLogic;

class ReportEodTrade extends BaseModel
{
    protected $table = 'm_report_eod_trade';

    /**
     * 列表数据
     */    
    public function listData($page, $pageSize, $params = []){
        $date = $params['date'] ?? date('Y-m-d');
        $where = [
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
            ['create_time', 'between', [$date, $date.' 23:59:59']],
        ];
        $list = $this->getDatasByPage($where, '*', $page, $pageSize, 'trade_entry_datetime desc');
        //细类显示名称
        $list['data'] = MapUtil::getMapdArrayByParams($list['data'] , 'trade_son_type' , 'pay_type');
        return $list;
    }

    /**
     * 获取付款指令
     */
    public function getPayOrder($date){
        $where = [
            ['require_pay_datetime', '<=', $date],
            ['order_status', 'not in', [PayOrder::ORDER_STATUS_REJECT]],
            ['pay_status', 'not in', [PayOrder::PAY_STATUS_PAID]],
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
        ];
        $list = $this->getList($where, '*', null, null, null, 'm_pay_order');
        foreach ($list as &$item) {
            $where = [
                ['pay_order_uuid', '=', $item['uuid']],
                ['require_pay_datetime', '<=', $date],
                ['transfer_status', 'not in', [PayTransfer::TRANSFER_STATUS_REJECT]],
                ['pay_status', 'not in', [PayTransfer::PAY_STATUS_PAID]],
                ['is_delete', '=', self::DEL_STATUS_NORMAL],
            ];
            $item['audit_log'] = $this->getAuditLog($item['uuid'], ['pay_order']);
            $transferData = $this->getOne($where, '*', null, 'm_pay_transfer');
            if (!empty($transferData)) {
                if ($this->getCount(['uuid' => $transferData['water_uuid']], 'm_sys_trade_water')) {
                    unset($item);
                    continue;
                }
                $transferData['audit_log'] = $this->getAuditLog($transferData['uuid'], ['pay_transfer_pay_type_1_code', 'pay_transfer_pay_type_2_code']);
                $item['transfer_data'] = $transferData;
            }
        }
        return $list;
    }

    /**
     * 获取借款数据
     */
    public function getLoan($date){
        $where = [
            ['loan_type', '=', 1],
            ['loan_datetime', '<=', $date],
            ['order_status', 'not in', [LoanOrder::ORDER_STATUS_REJECT]],
            ['loan_status', 'not in', [LoanOrder::LOAN_STATUS_PAID]],
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
        ];
        $list = $this->getList($where, '*', null, null, null, 'm_loan_order');
        foreach ($list as &$item) {
            $where = [
                ['loan_order_uuid', '=', $item['uuid']],
                ['loan_type', '=', 1],
                ['loan_datetime', '<=', $date],
                ['transfer_status', 'not in', [LoanTransfer::TRANSFER_STATUS_REJECT]],
                ['loan_status', 'not in', [LoanTransfer::LOAN_STATUS_PAID]],
                ['is_delete', '=', self::DEL_STATUS_NORMAL],
            ];
            $item['audit_log'] = $this->getAuditLog($item['uuid'], ['loan_order']);
            $transferData = $this->getOne($where, '*', null, 'm_loan_transfer');
            if (!empty($transferData)) {
                if ($this->getCount(['uuid' => $transferData['water_uuid']], 'm_sys_trade_water')) {
                    unset($item);
                    continue;
                }
                $transferData['audit_log'] = $this->getAuditLog($item['uuid'], ['loan_transfer_pay_type_1_code', 'loan_transfer_pay_type_2_code']);
                $item['transfer_data'] = $transferData;
            }
        }
        return $list;
    }

    public function getRepay($date){
        $where = [
            ['repay_date', '<=', $date],
            ['cash_flow_type', 'in', [2, 3]],
            ['transfer_status', 'not in', [LoanTransfer::TRANSFER_STATUS_REJECT]],
            ['pay_status', 'not in', [LoanTransfer::LOAN_STATUS_PAID]],
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
        ];
        $list = $this->getList($where, '*', null, null, null, 'm_loan_cash_flow');
        foreach ($list as &$item) {
            $where = ['uuid' => $item['loan_transfer_uuid'], 'is_delete' => self::DEL_STATUS_NORMAL];
            $transferData = $this->getOne($where, '*', null, 'm_loan_transfer');
            $transferData['audit_log'] = $this->getAuditLog($transferData['uuid'], ['repay_apply']);
            $item['transfer_data'] = $transferData;
            $where = ['uuid' => $item['loan_order_uuid'], 'is_delete' => self::DEL_STATUS_NORMAL];
            $orderData = $this->getOne($where, '*', null, 'm_loan_transfer');
            $orderData['audit_log'] = $this->getAuditLog($orderData['uuid'], ['repay_order']);
            $item['order_data'] = $orderData;
        }
    }

    /**
     * 获取内部调拨
     */
    public function getInnerTransfer($date){
        $where = [
            ['hope_deal_date', '<=', $date],
            ['transfer_status', 'not in', [InnerTransfer::TRANSFER_STATUS_REJECT]],
            ['pay_status', 'not in', [InnerTransfer::INNER_STATUS_PAID]],
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
        ];
        $list = $this->getList($where, '*', null, null, null, 'm_inner_transfer');
        foreach ($list as &$item) {
            if ($this->getCount(['uuid' => $item['water_uuid']], 'm_sys_trade_water')) {
                unset($item);
                continue;
            }
            $item['audit_log'] = $this->getAuditLog($item['uuid'], ['inner_transfer_pay_type_1_code', 'inner_transfer_pay_type_2_code']);
        }
        return $list;
    }

    /**
     * 获取理财数据
     */
    public function getPlan($date){
        $where = [
            ['repay_date', '<=', $date],
            ['status', 'not in', [MoneyPlan::PLAN_STATUS_REJECT]],
            ['is_delete', '=', self::DEL_STATUS_NORMAL],
        ];
        $list = $this->getList($where, '*', null, null, null, 'm_money_manager_cash_flow');
        foreach ($list as &$item) {
            $where = ['uuid' => $item['money_manager_plan_uuid'], 'is_delete' => self::DEL_STATUS_NORMAL];
            $planData = $this->getOne($where, '*', null, 'm_money_manager_plan');
            if ($this->getCount(['uuid' => $planData['sys_water']], 'm_sys_trade_water')) {
                unset($item);
                continue;
            }
            $item['plan_data'] = $planData;
            $instanceUuid = $item['cash_flow_type'] = 1 ? $planData['uuid'] : $item['uuid'];
            $item['audit_log'] = $this->getAuditLog($instanceUuid, ['financial_audit_pay_type_1_code', 'financial_audit_pay_type_2_code', 'redemption_audit_code_1_node']);
        }
        return $list;
    }

    private function getAuditLog($instanceUuid, array $flowCode = []){
        static $cLogic = null;
        if (empty($cLogic)) {
            $cLogic = new CommonLogic();
        }
        $list = $cLogic->getAuditLog($instanceUuid, $flowCode);
        return array_pop($list);
    }
}