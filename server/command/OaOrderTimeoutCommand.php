<?php
/**
 * Desc:
 * User: yanjiang.chen
 * Email: yanjiang.chen@yunjiaplus.com
 * Date: 2018/11/7
 * Time: 17:13
 */
namespace money\command;
use money\console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use money\logic\OaPayLogic;
use money\logic\OaLoanLogic;
use money\logic\OaRepayLogic;
class OaOrderTimeoutCommand extends Command
{
    protected $name = 'oa:order-timeout';
    protected $description = 'OA调用指令超时处理';

    protected function configure()
    {
        $this->help = <<<EOF
OA系统调用付款指令:

  <info>php %command.full_name%</info>

可选参数，超时时间（秒）:

  <info>php %command.full_name% -b 2100</info>

可选参数，系统标识，多个使用英文逗号隔开:

  <info>php %command.full_name% -s oa</info>
  
可选参数，流程编号，多个使用英文逗号隔开:

  <info>php %command.full_name% -w 69</info>

可选参数，请求编号，多个使用英文逗号隔开:

  <info>php %command.full_name% -r 11429</info>

EOF;
        $this->options = [
            ['timeout', 'b', InputOption::VALUE_REQUIRED, '超时时间', 2100],
            ['system_flag', 's', InputOption::VALUE_REQUIRED, '系统标识', 'oa'],
            ['workflowid', 'w', InputOption::VALUE_REQUIRED, '流程编号'],
            ['requestid', 'r', InputOption::VALUE_REQUIRED, '请求编号'],
        ];
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conditions = [
            'timeout' => time() - $input->getOption('timeout'),
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

        $this->payOrderTimeout($conditions);
        $this->loanOrderTimeout($conditions);
        $this->repayOrderTimeout($conditions);
    }

    /**
     * 处理OA付款指令超时处理失败
     * @param $params
     */
    private function payOrderTimeout($params)
    {
        $oaPayLogic = new OaPayLogic();
        $res = $oaPayLogic->batchTimeOutWorkflowRequestBase($params);
        foreach ($res as $k => $v) {
            $this->io->title('付款请求编号：'.$k);
            if ($v['code']) {
                $this->io->error(json_encode($v, JSON_UNESCAPED_UNICODE));
            } else {
                $this->io->success(json_encode($v, JSON_UNESCAPED_UNICODE));
            }
        }
    }

    private function loanOrderTimeout(array $params)
    {
        $oaLoanLogic = new OaLoanLogic();
        $res = $oaLoanLogic->batchTimeOutWorkflowRequestBase($params);
        foreach ($res as $k => $v) {
            $this->io->title('借款请求编号：'.$k);
            if ($v['code']) {
                $this->io->error(json_encode($v, JSON_UNESCAPED_UNICODE));
            } else {
                $this->io->success(json_encode($v, JSON_UNESCAPED_UNICODE));
            }
        }

    }

    private function repayOrderTimeout(array $params)
    {
        $oaRepayLogic = new OaRepayLogic();
        $res = $oaRepayLogic->batchTimeOutWorkflowRequestBase($params);
        foreach ($res as $k => $v) {
            $this->io->title('还款请求编号：'.$k);
            if ($v['code']) {
                $this->io->error(json_encode($v, JSON_UNESCAPED_UNICODE));
            } else {
                $this->io->success(json_encode($v, JSON_UNESCAPED_UNICODE));
            }
        }
    }
}