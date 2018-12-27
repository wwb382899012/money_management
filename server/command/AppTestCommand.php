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

class AppTestCommand extends Command
{
    protected $name = 'app:test';
    protected $help = 'This command allows you to create a user...';
    protected $description = 'Creates a new user.';
    protected $arguments = [
        ['name', InputArgument::REQUIRED, 'Who do you want to greet?'],
        ['last_name', InputArgument::OPTIONAL, 'Your last name?'],
    ];
    protected $options = [
        ['iterations', 'i', InputOption::VALUE_REQUIRED, 'How many times should the message be printed?', 1],
        ['colors', 'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Which colors do you like?', ['blue', 'red']],
    ];

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'User Creator',
            '============',
            '',
        ]);
        $text = 'Hi '.$input->getArgument('name');

        $lastName = $input->getArgument('last_name');
        if ($lastName) {
            $text .= ' '.$lastName;
        }

        $output->writeln($text.'!');

        $iterations = $input->getOption('iterations');
        $output->writeln($iterations.' times');

        $color = $input->getOption('colors');
        $output->writeln($color);
    }
}
