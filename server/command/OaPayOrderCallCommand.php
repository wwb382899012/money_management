<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/23
 * Time: 23:26
 */

namespace money\command;

use money\console\Command;
use money\logic\OaPayLogic;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class OaPayOrderCallCommand extends Command
{
    protected $name = 'oa:pay-order-call';
    protected $description = 'OA系统调用付款指令';

    protected function configure()
    {
        $this->help = <<<EOF
OA系统调用付款指令:

  <info>php %command.full_name%</info>

可选参数，开始时间，结束时间:

  <info>php %command.full_name% -b 2018-06-01 -e 2018-07-01</info>

可选参数，系统标识，多个使用英文逗号隔开:

  <info>php %command.full_name% -s oa</info>
  
可选参数，流程编号，多个使用英文逗号隔开:

  <info>php %command.full_name% -w 69</info>

可选参数，请求编号，多个使用英文逗号隔开:

  <info>php %command.full_name% -r 11429</info>

EOF;
        $this->options = [
            ['begin_time', 'b', InputOption::VALUE_REQUIRED, '开始时间', date('Y-m-d H:i:s', time() - 1800)],
            ['end_time', 'e', InputOption::VALUE_REQUIRED, '结束时间', date('Y-m-d H:i:s')],
            ['system_flag', 's', InputOption::VALUE_REQUIRED, '系统标识', 'oa'],
            ['workflowid', 'w', InputOption::VALUE_REQUIRED, '流程编号'],
            ['requestid', 'r', InputOption::VALUE_REQUIRED, '请求编号'],
        ];
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conditions = [
            'begin_time' => strtotime($input->getOption('begin_time')),
            'end_time' => strtotime($input->getOption('end_time')),
        ];
        if ($systemFlag = $input->getOption('system_flag')) {
            $conditions['system_flag'] = explode(',', $systemFlag);
        }
        if ($workflowId = $input->getOption('workflowid')) {
            $conditions['workflowid'] = explode(',', $workflowId);
        }
        if ($requestId = $input->getOption('requestid')) {
            $conditions['requestid'] = explode(',', $requestId);
        }
        $oaPayLogic = new OaPayLogic();
        $res = $oaPayLogic->batchCallPayOrder($conditions);
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
