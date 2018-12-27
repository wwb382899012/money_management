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

class UserAdminEnableCommand extends Command
{
    protected $name = 'user:admin-enable';
    protected $description = '启用管理员用户';
    protected $arguments = [
        ['enable', InputArgument::REQUIRED, '是否启用管理员用户，0不启用，1启用'],
    ];

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $status = $input->getArgument('enable') ? 1 : 2;
        $mSysUser = new SysUser();
        $where = ['username' => 'admin'];
        if ($mSysUser->getCount($where)) {
            $res = $mSysUser->where($where)->update(['status' => $status]);
        } else {
            $data = [
                'uuid' => md5(uuid_create()),
                'user_id' => 1,
                'identifier' => 1,
                'username' => 'admin',
                'name' => '管理员',
            ];
            $res = $mSysUser->insert($data);
        }
        $res ? $this->io->success('success') : $this->io->error('fail');
    }
}
