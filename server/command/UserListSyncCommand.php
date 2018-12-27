<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/23
 * Time: 23:26
 */

namespace money\command;

use money\console\Command;
use money\model\SysUser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class UserListSyncCommand extends Command
{
    protected $name = 'user:list-sync';
    protected $description = '用户列表同步';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        require_once SERVER_PATH . 'user/index.php';
        $mSysUser = new SysUser();
        $mSysUser->syncData();
        $this->io->success('用户列表同步完毕');
    }
}
