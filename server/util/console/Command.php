<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/23
 * Time: 23:26
 */

namespace money\console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Command extends \Symfony\Component\Console\Command\Command
{
    protected $name;
    protected $processTitle;
    protected $aliases;
    protected $hidden;
    protected $help;
    protected $description;
    protected $usages;
    protected $arguments;
    protected $options;
    /**
     * @var SymfonyStyle
     */
    protected $io;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function configure()
    {
        isset($this->name) && $this->setName($this->name);
        isset($this->processTitle) && $this->setProcessTitle($this->processTitle);
        isset($this->aliases) && $this->setAliases($this->aliases);
        isset($this->hidden) && $this->setHidden($this->hidden);
        isset($this->help) && $this->setHelp($this->help);
        isset($this->description) && $this->setDescription($this->description);
        if (isset($this->usages) && is_array($this->usages)) {
            foreach ($this->usages as $usage) {
                $this->addUsage($usage);
            }
        }
        if (isset($this->arguments) && is_array($this->arguments)) {
            foreach ($this->arguments as $argument) {
                is_array($argument) && $this->addArgument(...$argument);
            }
        }
        if (isset($this->options) && is_array($this->options)) {
            foreach ($this->options as $option) {
                is_array($option) && $this->addOption(...$option);
            }
        }
    }
}
