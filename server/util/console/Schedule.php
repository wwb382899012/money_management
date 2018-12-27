<?php
/**
 * Created by PhpStorm.
 * User: xupengpeng
 * Date: 2018/9/29
 * Time: 14:09
 */

namespace money\console;

use money\Config;
use Cron\CronExpression;
use Swoole\Process;

class Schedule
{
    protected $appName = '';
    protected $configs = [];
    /**
     * @var array
     *  [
     *      'pid' => 123,//子进程pid
     *      'start_time' => 1538219067,//子进程开始运行时间
     *  ]
     */
    protected $childProcess = [];
    protected $childProcessCount = 0;

    public function __construct()
    {
        pcntl_async_signals(true);
        $this->registerSignal();
        if (!empty($this->appName) ) {
            Config::load(SERVER_PATH . 'config/schedule.php', '', 'schedule');
            $this->configs = Config::get($this->appName, 'schedule');
        }
    }

    public function workerTimer($params)
    {

    }

    public function workerStart($params)
    {

    }

    public function workerStop($server, $workerId)
    {

    }

    public function taskStart($params)
    {
        while (!empty($this->configs)) {
            foreach ($this->configs as &$config) {
                $this->parseCommand($config);
                if (!$this->shouldRun($config)) {
                    continue;
                }
                $this->doRun($config);
            }
            if ($this->childProcessCount > 1000) {
                $this->killAllProcess();
            }
            //分钟级定时器，不直接休眠60s，避免跨过执行时间点
            $m = date('i');
            while ($m == date('i')) {
                sleep(5);
            }
        }
    }

    public function taskStop($server, $workerId)
    {

    }

    /**
     * 注册信号量
     */
    protected function registerSignal()
    {
        pcntl_signal(SIGQUIT, [$this, 'killAllProcess']);
        pcntl_signal(SIGILL, [$this, 'killAllProcess']);
        pcntl_signal(SIGUSR1, [$this, 'killAllProcess']);
        pcntl_signal(SIGUSR2, [$this, 'killAllProcess']);
        pcntl_signal(SIGTERM, [$this, 'killAllProcess']);
        pcntl_signal(SIGCHLD, [$this, 'waitChildProcess']);
    }

    /**
     * 杀死所有子进程，并退出主进程
     */
    public function killAllProcess()
    {
        foreach ($this->childProcess as $process) {
            isset($process['pid']) && posix_kill($process['pid'],SIGKILL);
        }
        exit;
    }

    /**
     * 回收结束运行的子进程
     */
    public function waitChildProcess()
    {
        //必须为false，非阻塞模式
        while($ret = Process::wait(false)) {
            //$ret = array('code' => 0, 'pid' => 15001, 'signal' => 15);
            foreach ($this->childProcess as $command => $process) {
                if ($ret['pid'] == $process['pid']) {
                    unset($this->childProcess[$command]);
                    break;
                }
            }
        }
    }

    /**
     * 判断进程是否正在运行
     * @param string $command
     * @param int $timeout
     * @return bool
     */
    protected function isRunning($command, $timeout = 0)
    {
        if (!isset($this->childProcess[$command])) {
            return false;
        }
        $process = $this->childProcess[$command];
        if (!isset($process['pid']) || !posix_kill($process['pid'], SIG_DFL)) {
            unset($this->childProcess[$command]);
            return false;
        }
        //进程运行时间超过最大时间，则杀死进程
        if (!empty($timeout) && time() - $process['start_time'] > $timeout && posix_kill($process['pid'], SIGKILL)) {
            unset($this->childProcess[$command]);
            return false;
        }
        return true;
    }

    /**
     * 判断命令是否执行
     * @param array $config
     * @return bool
     */
    protected function shouldRun($config)
    {
        if (!isset($config['command']) || !isset($config['schedule']) || !$config['enabled']) {
            return false;
        }

        //判断进程是否正在运行
        if ($this->isRunning($config['command'], $config['timeout'] ?? 0)) {
            return false;
        }

        //判断进程是否应该运行
        if (!CronExpression::factory($config['schedule'])->isDue()) {
            return false;
        }

        return true;
    }

    /**
     * 解析command
     * @wiki https://wiki.swoole.com/wiki/page/263.html
     * @param array $config
     */
    protected function parseCommand(&$config)
    {
        if (!isset($config['command']) || isset($config['full_command'])) {
            return;
        }
        $command = $config['command'];
        if (is_string($command)) {
            $command = explode(' ', $command);
        }
        if (stripos($command[0], 'php') !== false) {
            $command[0] = PHP_BINDIR.'/php';
        }
        if (stripos($command[1], 'console') !== false) {
            $command[1] = SERVER_PATH.'bin/console';
        }
        $exec = array_shift($command);
        $config['full_command'] = [$exec, $command];
    }

    /**
     * 开启子进程执行命令
     * @param $config
     */
    protected function doRun($config)
    {
        $command = $config['command'];
        $fullCommand = $config['full_command'];
        $process = new Process(function (Process $p) use ($fullCommand) {
            $p->exec($fullCommand[0], $fullCommand[1]);
        }, true);

        if ($pid = $process->start()) {
            $this->childProcess[$command] = [
                'pid' => $pid,
                'start_time' => time(),
            ];
            ++$this->childProcessCount;
        }
    }
}