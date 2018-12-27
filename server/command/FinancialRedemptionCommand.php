<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/23
 * Time: 23:26
 */

namespace money\command;

use money\console\Command;
use money\logic\EventProvider;
use money\model\EodTradeDb;
use money\model\MoneyPlan;
use money\model\SysUser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class FinancialRedemptionCommand extends Command
{
    protected $name = 'financial:redemption';
    protected $description = '理财赎回';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = date('Y-m-d');
        $mMoneyPlan = new MoneyPlan();
        $event = new EventProvider();
        $where = [
            ['term_type', '=', 2],
            ['rate_start_date', '<=', $now],
            //['rate_over_date', '<=', $now],
            ['plan_status', 'IN', [MoneyPlan::PLAN_STATUS_WAIT_TICKET_BACK, MoneyPlan::PLAN_STATUS_ARCHIVE]],
            ['pay_status', '=', MoneyPlan::PAY_STATUS_PAID],
            ['is_pay_off', '=', 1],
            ['is_delete', '=', MoneyPlan::DEL_STATUS_NORMAL],
        ];
        //查询未还清的理财计划
        $moneyPlanList = $mMoneyPlan->getAll($where);
        foreach ($moneyPlanList as $item) {
            $where = [
                ['money_manager_plan_uuid', '=', $item['uuid']],
                ['repay_date', '<=', $now],
                ['cash_flow_type', 'IN', [2, 3]],
                ['status', '=', MoneyPlan::PLAN_STATUS_SAVED],
                ['is_delete', '=', MoneyPlan::DEL_STATUS_NORMAL],
            ];
            if ($mMoneyPlan->table('m_money_manager_cash_flow')->where($where)->update(['status' => MoneyPlan::PLAN_STATUS_WAIT_TICKET_BACK])) {
                //EOD报表
                $params = [
                    'transfer_num'=>$item['money_manager_plan_num'],
                    'main_body_uuid' => $item['plan_main_body_uuid'],
                    'transfer_create_time' => date('Y-m-d H:i:s'),
                    'limit_date' => $item['rate_over_date'],
                    'opt_uuid' => $item['uuid'],
                    'trade_type' => 9,
                ];
                EodTradeDb::dataCreate($params);
                
                $where = ['uuid' => $item['money_manager_product_uuid'], 'status' => 1, 'is_delete' => MoneyPlan::DEL_STATUS_NORMAL];
                $product = $mMoneyPlan->getOne($where, 'product_name', null, 'm_money_manager_product');
                //消息提醒
                $eventData['plan_uuid'] = $item['uuid'];
                $eventData['money_manager_plan_num'] = $item['money_manager_plan_num'];
                $eventData['money_manager_product_name'] = $product['product_name'];
                $eventData['term_type'] = $item['term_type'];
                $eventData['amount'] = $item['amount'];
                $eventData['create_user_id'] = $item['create_user_id'];
                $eventData['create_user_name'] = $item['create_user_name'];
                $eventData['audit_datetime'] = date('Y-m-d H:i:s');
                $eventData['node_code'] = 'redemption_audit_node_no_2';
                $eventData['cur_audit_control_type'] = 2;
                $userInfos = SysUser::getUserInfoByIds([$item['create_user_id']]);
                $users = array();
                foreach ($userInfos as $u) {
                    $users[] = [
                        'name' => $u['name'],
                        'id' => $u['user_id'],
                        'email' => $u['email'],
                    ];
                }
                $eventData['next_audit_user_infos'] = $users;
                $event->redemAuditEvent($eventData);
            }
        }
        $this->io->success('理财赎回处理完毕');
    }
}
