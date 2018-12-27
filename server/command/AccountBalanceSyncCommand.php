<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/23
 * Time: 23:26
 */

namespace money\command;

use money\model\BankAccount;
use money\console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class AccountBalanceSyncCommand extends Command
{
    protected $name = 'account:balance-async';
    protected $description = '同步账户余额';

    protected function configure()
    {
        $this->help = <<<EOF
同步账户余额:

  <info>php %command.full_name% <file></info>

EOF;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $where = ['is_delete' => BankAccount::DEL_STATUS_NORMAL, 'status' => 0];
        $mBankAccount = new BankAccount();
        $count = $mBankAccount->getCount($where);
        $pageSize = 1000;
        $page = ceil($count / $pageSize);
        while ($count > 0) {
            $list = $mBankAccount->getList($where, 'uuid, bank_account, bank_dict_key, area', $page, $pageSize);
            $mBankAccount->syncBalance($list);
            $count -= $pageSize;
        }
        $this->io->success('同步账户余额完毕');
    }
}
