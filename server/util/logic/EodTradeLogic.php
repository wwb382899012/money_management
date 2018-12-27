<?php
/**
 * 全量交易处理逻辑
 */

namespace money\logic;

use money\model\ReportEodTrade;

class EodTradeLogic{
    /**
     * @var ReportEodTrade
     */
    private $mReportEodTrade;

    public function start($date = null){
        set_time_limit(0);
        ignore_user_abort(true);
        !isset($date) && $date = date('Y-m-d');
        $this->mReportEodTrade = new ReportEodTrade();
        $this->deleteAllData($date);
        $this->generatePayOrder($date);
        $this->generateLoan($date);
        //$this->generateRepay($date);//TODO 借款还款
        $this->generateInnerTransfer($date);
        $this->generatePlan($date);	
    }

    protected function deleteAllData($date){
        $where = [
            ['create_time', 'between', [$date, $date.' 23:59:59']],
        ];
        $this->mReportEodTrade::update(['is_delete' => 2], $where);
    }

    protected function generatePayOrder($date){
        $list = $this->mReportEodTrade->getPayOrder($date);
        $insertData = [];
        foreach ($list as $item) {
            $insertData[] = [
                'uuid' => md5(uuid_create()),
                'out_order_num' => $item['out_order_num'],
                'trade_order_num' => $item['transfer_data']['transfer_num'] ?? '',
                'trade_uuid' => $item['transfer_data']['uuid'] ?? '',
                'trade_type' => 1,
                'trade_son_type' => $item['pay_order_type'],
                'trade_entry_datetime' => $item['create_time'],
                'trade_receive_datetime' => $item['audit_log']['create_time'] ?? null,
                'trade_create_datetime' => $item['transfer_data']['create_time'] ?? null,
                'trade_audit_datetime' => $item['transfer_data']['audit_log']['create_time'] ?? null,
                'create_time' => date('Y-m-d H:i:s'),
            ];
        }
        $this->mReportEodTrade->insertAll($insertData);
    }

    protected function generateLoan($date){
        $list = $this->mReportEodTrade->getLoan($date);
        $insertData = [];
        foreach ($list as $item) {
            $insertData[] = [
                'uuid' => md5(uuid_create()),
                'out_order_num' => $item['out_order_num'],
                'trade_order_num' => $item['transfer_data']['transfer_num'] ?? '',
                'trade_uuid' => $item['transfer_data']['uuid'] ?? '',
                'trade_type' => 2,
                'trade_son_type' => $item['loan_type'] == 1 ? 'loan' : 'repay',
                'trade_entry_datetime' => $item['create_time'],
                'trade_receive_datetime' => $item['audit_log']['create_time'] ?? null,
                'trade_create_datetime' => $item['transfer_data']['create_time'] ?? null,
                'trade_audit_datetime' => $item['transfer_data']['audit_log']['create_time'] ?? null,
                'create_time' => date('Y-m-d H:i:s'),
            ];
        }
        $this->mReportEodTrade->insertAll($insertData);
    }

    protected function generateRepay($date){
        $list = $this->mReportEodTrade->getRepay($date);
        $insertData = [];
        foreach ($list as $item) {
            $insertData[] = [
                'uuid' => md5(uuid_create()),
                'out_order_num' => $item['out_order_num'],
                'trade_order_num' => $item['transfer_data']['transfer_num'] ?? '',
                'trade_uuid' => $item['transfer_data']['uuid'] ?? '',
                'trade_type' => 2,
                'trade_son_type' => $item['loan_type'] == 1 ? 'loan' : 'repay',
                'trade_entry_datetime' => $item['create_time'],
                'trade_receive_datetime' => $item['create_time'],
                'trade_create_datetime' => $item['transfer_data']['create_time'] ?? null,
                'trade_audit_datetime' => $item['transfer_data']['audit_log']['create_time'] ?? null,
                'create_time' => date('Y-m-d H:i:s'),
            ];
        }
        $this->mReportEodTrade->insertAll($insertData);
    }

    protected function generateInnerTransfer($date){
        $list = $this->mReportEodTrade->getInnerTransfer($date);
        $insertData = [];
        foreach ($list as $item) {
            $insertData[] = [
                'uuid' => md5(uuid_create()),
                'out_order_num' => '',
                'trade_order_num' => $item['order_num'],
                'trade_uuid' => $item['uuid'],
                'trade_type' => 3,
                'trade_son_type' => '',
                'trade_entry_datetime' => $item['create_time'],
                'trade_receive_datetime' => $item['create_time'],
                'trade_create_datetime' => $item['create_time'],
                'trade_audit_datetime' => $item['audit_log']['create_time'] ?? null,
                'create_time' => date('Y-m-d H:i:s'),
            ];
        }
        $this->mReportEodTrade->insertAll($insertData);
    }

    protected function generatePlan($date){
        $list = $this->mReportEodTrade->getPlan($date);
        $insertData = [];
        foreach ($list as $item) {
            $insertData[] = [
                'uuid' => md5(uuid_create()),
                'out_order_num' => '',
                'trade_order_num' => $item['plan_data']['money_manager_plan_num'] ?? '',
                'trade_uuid' => $item['plan_data']['uuid'] ?? '',
                'trade_type' => 4,
                'trade_son_type' => '',
                'trade_entry_datetime' => $item['create_time'],
                'trade_receive_datetime' => $item['create_time'],
                'trade_create_datetime' => $item['create_time'],
                'trade_audit_datetime' => $item['audit_log']['create_time'] ?? null,
                'create_time' => date('Y-m-d H:i:s'),
            ];
        }
        $this->mReportEodTrade->insertAll($insertData);
    }
}