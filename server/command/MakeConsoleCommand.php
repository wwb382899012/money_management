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

class MakeConsoleCommand extends Command
{
    protected $name = 'make:console';
    protected $description = '生成config/console.php配置文件';

    protected function configure()
    {
        $this->help = <<<EOF
自动生成config/console.php配置文件:

  <info>php %command.full_name%</info>

EOF;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $str = <<<EOL
<?php
/**
 * 执行php server/bin/console {$this->name}生成此配置文件
 */

return [

EOL;
        $list = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__, \FilesystemIterator::SKIP_DOTS));
        foreach ($list as $key => $file) {
            $fileName = pathinfo($file, PATHINFO_FILENAME);
            if ($fileName == 'Command' || strpos($fileName, '.') === 0) {
                continue;
            }
            $className = "money\\command\\".$fileName;
            $reflection = new \ReflectionClass($className);
            $nameProperty = $reflection->getProperty('name');
            $nameProperty->setAccessible(true);
            $name = $nameProperty->getValue(new $className);
            $str .= <<<EOL
    '$name' => function () { return new $className(); },

EOL;
        }
        $str .= '];'.PHP_EOL;
        file_put_contents(SERVER_PATH.'config/console.php', $str);
        $this->io->success('配置文件生成完毕');
    }
}
