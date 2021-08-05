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
            ->addOption('host', null, Option::VALUE_OPTIONAL, 'mysql host', '127.0.0.1')
            ->addOption('port', null, Option::VALUE_OPTIONAL, 'mysql port', '3306')
            ->addOption('database', null, Option::VALUE_OPTIONAL, 'mysql database name', 'nicms')
            ->addOption('prefix', null, Option::VALUE_OPTIONAL, 'table prefix', 'nc_')
            ->addOption('username', null, Option::VALUE_OPTIONAL, 'mysql username', 'root')
            ->addOption('password', null, Option::VALUE_OPTIONAL, 'mysql password', '')
            ->addOption('force', null, Option::VALUE_OPTIONAL, 'force override', false)
            ->addOption('admin_username', null, Option::VALUE_OPTIONAL, 'your manage username', 'admin')
            ->addOption('admin_password', null, Option::VALUE_OPTIONAL, 'your manage password', 'admin888')
            ->setDescription('the install command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 系统兼容
        $compatible = true;
        if (!version_compare(PHP_VERSION, '7.3.0', '>=')) {
            $compatible = false;
            $output->writeln('<info>系统需要PHP7.3+版本! 当前PHP版本:' . PHP_VERSION . '</info>');
        }
        if (!version_compare(app()->version(), '6.0.0', '>=')) {
            $compatible = false;
            $output->writeln('<info>系统需要ThinkPHP 6.0+版本! 当前ThinkPHP版本:' . app()->version() . '</info>');
        }
        if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
            $compatible = false;
            $output->writeln('<info>系统需要PDO和PDO_MYSQL模块!</info>');
        }
        if (!class_exists('ZipArchive')) {
            $compatible = false;
            $output->writeln('<info>环境不支持 ZipArchive 方法,系统备份功能无法使用</info>');
        }
        if (!function_exists('file_put_contents')) {
            $compatible = false;
            $output->writeln('<info>环境不支持 file_put_contents 函数,系统无法写文件</info>');
        }
        if (!function_exists('file_put_contents') || !function_exists('fopen')) {
            $compatible = false;
            $output->writeln('<info>环境不支持 file_put_contents 或 fopen等函数,系统无法写文件</info>');
        }
        if (!get_extension_funcs('gd')) {
            $compatible = false;
            $output->writeln('<info>环境不支持 gd 模块,图片水印和缩略生成功能无法使用</info>');
        }

        if ($compatible) {
            $host     = $input->getOption('host');
            $port     = $input->getOption('port');
            $database = $input->getOption('database');
            $prefix   = $input->getOption('prefix');
            $username = $input->getOption('username');
            $password = $input->getOption('password');
            $force    = $input->getOption('force');
            $admin_username = $input->getOption('admin_username');
            $admin_password = $input->getOption('admin_password');


        }
    }
}
