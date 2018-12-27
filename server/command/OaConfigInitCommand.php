<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/23
 * Time: 23:26
 */

namespace money\command;

use money\console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class OaConfigInitCommand extends Command
{
    protected $name = 'oa:config-init';
    protected $description = '初始化OA配置';

    protected function configure()
    {
        $this->help = <<<EOF
初始化OA的所有配置:

  <info>php %command.full_name%</info>

初始化OA的付款配置:

  <info>php %command.full_name% -b pay</info>

初始化OA的借款配置:

  <info>php %command.full_name% -b loan</info>

初始化OA的还款配置:

  <info>php %command.full_name% -b repay</info>

EOF;
        $this->options = [
            ['business_type', 'b', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, '业务名称', ['pay', 'loan', 'repay']],
        ];
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('business_type');
        foreach ($type as $item) {
            $class = "\\money\\model\\Oa".ucfirst($item)."Business";
            $m = new $class();
            if ($m->initData()) {
                $message = "初始化OA的{$item}配置成功";
                $this->io->success($message);
            } else {
                $message = "初始化OA的{$item}配置失败：" . json_encode($m->getError(), JSON_UNESCAPED_UNICODE);
                $this->io->error($message);
            }
        }
    }
}
