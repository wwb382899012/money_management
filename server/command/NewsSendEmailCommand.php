<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/23
 * Time: 23:26
 */

namespace money\command;

use money\console\Command;
use money\logic\EmailBatchSend;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class NewsSendEmailCommand extends Command
{
    protected $name = 'news:send-email';
    protected $description = '批量发送邮件';

    protected function configure()
    {
        $this->help = <<<EOF
批量发送邮件:

  <info>php %command.full_name% <file></info>

EOF;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = new EmailBatchSend();
        $email->start();
        $this->io->success('批量发送邮件完毕');
    }
}
