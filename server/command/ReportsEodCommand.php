<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/23
 * Time: 23:26
 */

namespace money\command;

use money\console\Command;
use money\model\EodTradeDb;
use money\model\LoanCashFlow;
use money\model\Repay;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ReportsEodCommand extends Command
{
    protected $name = 'reports:eod';
    protected $description = 'EOD日终报表';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //借款还款
        $where = [
            ['cash_flow_type', '=', 2],
            ['status', '=', 0],
            ['forcast_repay_date', '<', date('Y-m-d')],
            ['is_delete', '=', Repay::DEL_STATUS_NORMAL],
        ];
        $mLoanCashFlow = new LoanCashFlow();
        $mRepayTransfer = new Repay();
        $list = $mLoanCashFlow->getAll($where);
        foreach ($list as $item) {
            $where = [
                ['loan_transfer_uuid', '=', $item['loan_transfer_uuid']],
                ['is_delete', '=', Repay::DEL_STATUS_NORMAL],
            ];
            if ($repayTransfer = $mRepayTransfer->getOne($where, 'id,repay_transfer_num,repay_main_body_uuid,forcast_date')) {
                $params = [
                    'transfer_num' => $repayTransfer['repay_transfer_num'],
                    'main_body_uuid' => $repayTransfer['repay_main_body_uuid'],
                    'transfer_create_time' => date('Y-m-d H:i:s'),
                    'limit_date' => $repayTransfer['forcast_date'],
                    'opt_uuid' => $repayTransfer['id'],
                    'trade_type' => 6,
                ];
                EodTradeDb::dataCreate($params);
            }
        }
        $this->io->success('EOD日终报表处理完毕');
    }
}
