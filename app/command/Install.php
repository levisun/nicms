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
        if (false === $this->testing($output)) {
            return;
        }

        if (!$username = $input->getOption('admin_username')) {
            $output->writeln('username');
            return;
        }
        if (!$password = $input->getOption('admin_password')) {
            $output->writeln('<info>123</info>');
        }




        // $output->writeln("Hello," . $username . '!' . $password);


    }

    /**
     *
     */
    private function testing(Output $output): bool
    {
        $result = true;
        if (!version_compare(PHP_VERSION, '7.3.0', '>=')) {
            $output->writeln('<info>系统需要PHP7.3+版本! 当前PHP版本:' . PHP_VERSION . '</info>');
            $result = false;
        }

        if (!version_compare(app()->version(), '6.0.0', '>=')) {
            $output->writeln('<info>系统需要ThinkPHP 6.0+版本! 当前ThinkPHP版本:' . app()->version() . '</info>');
            $result = false;
        }

        if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
            $output->writeln('<info>系统需要PDO和PDO_MYSQL模块!</info>');
            $result = false;
        }

        if (!class_exists('ZipArchive')) {
            $output->writeln('<info>空间不支持 ZipArchive 方法,系统备份功能无法使用</info>');
            $result = false;
        }

        if (!function_exists('file_put_contents')) {
            $output->writeln('<info>空间不支持 file_put_contents 函数,系统无法写文件</info>');
            $result = false;
        }

        if (!function_exists('file_put_contents') || !function_exists('fopen')) {
            $output->writeln('<info>空间不支持 file_put_contents 或 fopen等函数,系统无法写文件</info>');
            $result = false;
        }

        if (!get_extension_funcs('gd')) {
            $output->writeln('<info>空间不支持 gd 模块,图片水印和缩略生成功能无法使用</info>');
            $result = false;
        }

        return $result;
    }
}
