<?php

declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Install extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('install')
            ->addOption('hostname', 'a', Option::VALUE_OPTIONAL, 'mysql hostname', '127.0.0.1')
            ->addOption('hostport', 'o', Option::VALUE_OPTIONAL, 'mysql hostport', '3306')
            ->addOption('database', 'd', Option::VALUE_OPTIONAL, 'mysql database', 'nicms')
            ->addOption('prefix', 'r', Option::VALUE_OPTIONAL, 'table prefix', 'nc_')
            ->addOption('username', 'u', Option::VALUE_OPTIONAL, 'mysql username', 'root')
            ->addOption('password', 'p', Option::VALUE_OPTIONAL, 'mysql password', '')
            ->addOption('force', 'f', Option::VALUE_OPTIONAL, 'force override', false)
            ->addOption('admin_username', 'au', Option::VALUE_OPTIONAL, 'your manage username', 'admin')
            ->addOption('admin_password', 'ap', Option::VALUE_OPTIONAL, 'your manage password', 'admin888')
            ->setDescription('the install command');
    }

    protected function execute(Input $input, Output $output)
    {
        if (!$username = $input->getOption('admin_username')) {
            $output->writeln('username');
            return;
        }
        if (!$password = $input->getOption('admin_password')) {
            $output->writeln('<info>123</info>');
        }




        // $output->writeln("Hello," . $username . '!' . $password);

        // is_writable
    }
}
