<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/23
 * Time: 23:26
 */

namespace money\command;

use money\console\Command;
use money\logic\FullTradeLogic;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ReportsFullCommand extends Command
{
    protected $name = 'reports:full';
    protected $description = '全量报表';

    protected function configure()
    {
        $this->help = <<<EOF
全量报表:

  <info>php %command.full_name%</info>

可选参数，开始时间:

  <info>php %command.full_name% -b 2018-06-01</info>

EOF;
        $this->options = [
            ['begin_time', 'b', InputOption::VALUE_REQUIRED, '开始时间', date('Y-m-d H:i:s', time() - 1800)],
        ];
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $beginTime = $input->getOption('begin_time');
        $fLogic = new FullTradeLogic();
        $fLogic->start($beginTime);
        $this->io->success('全量报表处理完毕');
    }
}
