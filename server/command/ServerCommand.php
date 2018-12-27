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

class ServerCommand extends Command
{
    protected $name = 'server';
    protected $description = '服务管理';
    protected $arguments = [
        ['server_name', InputArgument::REQUIRED, '服务名称'],
        ['action', InputArgument::REQUIRED, '动作'],
    ];

    protected function configure()
    {
        $this->help = <<<EOF
启动服务:

  <info>php %command.full_name% <server_name> start</info>
  
重启|平滑重启服务:

  <info>php %command.full_name% <server_name> restart|reload</info>
  
关闭服务:

  <info>php %command.full_name% <server_name> stop</info>
  
启动所有服务:

  <info>php %command.full_name% all start</info>
  
重启|平滑重启所有服务:

  <info>php %command.full_name% all restart|reload</info>
  
关闭所有服务:

  <info>php %command.full_name% all stop</info>

EOF;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serverName = $input->getArgument('server_name');
        $action = $input->getArgument('action');
        $serverNameList = [];
        if ($serverName == 'all') {
            if($handle = opendir(SERVER_PATH)){
                while (false !== ($file = readdir($handle))){
                    $providerFile = SERVER_PATH.$file.DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'provider.properties';
                    if (is_file($providerFile)) {
                        $serverNameList[] = $file;
                    }
                }
                closedir($handle);
            }
        } else {
            $serverNameList[] = $serverName;
        }

        foreach ($serverNameList as $name) {
            if ($action == 'reload') {
                $masterProcessName = $name . '_master_process';
                $command = "if [ $(ps -ef | grep $masterProcessName | grep -v grep | wc -l) -gt 0 ]; then kill -USR1 $(pidof $masterProcessName); else echo 'service not started'; return 1; fi";
            } else {
                $command = PHP_BINDIR.'/php '.dirname(SERVER_PATH).'/jyb_microservice_framework/bin/jmfServer.php '.$name.' '.$action;
            }
            $this->io->title($command);
            exec($command, $out, $status);
            $status ? $this->io->error($out) : $this->io->success('success');
            unset($out, $status);
        }
    }
}
