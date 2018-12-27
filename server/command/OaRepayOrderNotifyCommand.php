<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/23
 * Time: 23:26
 */

namespace money\command;

use money\console\Command;
use money\logic\OaRepayLogic;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class OaRepayOrderNotifyCommand extends Command
{
    protected $name = 'oa:repay-order-notify';
    protected $description = '资金系统通知OA系统还款状态';

    protected function configure()
    {
        $this->help = <<<EOF
资金系统通知OA系统还款状态:

  <info>php %command.full_name%</info>

可选参数，开始时间，结束时间:

  <info>php %command.full_name% -b 2018-06-01 -e 2018-07-01</info>
  
可选参数，还款指令编号，多个使用英文逗号隔开:

  <info>php %command.full_name% -n 11429</info>

EOF;
        $this->options = [
            ['begin_time', 'b', InputOption::VALUE_REQUIRED, '开始时间', date('Y-m-d H:i:s', time() - 1800)],
            ['end_time', 'e', InputOption::VALUE_REQUIRED, '结束时间', date('Y-m-d H:i:s')],
            ['order_num', 'r', InputOption::VALUE_REQUIRED, '还款指令编号'],
        ];
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conditions = [
            'begin_time' => strtotime($input->getOption('begin_time')),
            'end_time' => strtotime($input->getOption('end_time')),
        ];
        if ($orderNum = $input->getOption('order_num')) {
            $conditions['order_num'] = explode(',', $orderNum);
        }
        $oaRepayLogic = new OaRepayLogic();
        $res = $oaRepayLogic->batchNotifyOa($conditions);
        foreach ($res as $k => $v) {
            $this->io->title('请求编号：'.$k);
            if ($v['code']) {
                $this->io->error(json_encode($v, JSON_UNESCAPED_UNICODE));
            } else {
                $this->io->success(json_encode($v, JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
