<?php

declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Test extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('test')
            ->setDescription('the test command');
    }

    protected function execute(Input $input, Output $output)
    {
        if (!version_compare(PHP_VERSION, '7.3.0', '>=')) {
            $output->writeln('<info>系统需要PHP7.3+版本! 当前PHP版本:' . PHP_VERSION . '</info>');
        } elseif (!version_compare(app()->version(), '6.0.0', '>=')) {
            $output->writeln('<info>系统需要ThinkPHP 6.0+版本! 当前ThinkPHP版本:' . app()->version() . '</info>');
        } elseif (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
            $output->writeln('<info>系统需要PDO和PDO_MYSQL模块!</info>');
        } elseif (!class_exists('ZipArchive')) {
            $output->writeln('<info>环境不支持 ZipArchive 方法,系统备份功能无法使用</info>');
        } elseif (!function_exists('file_put_contents')) {
            $output->writeln('<info>环境不支持 file_put_contents 函数,系统无法写文件</info>');
        } elseif (!function_exists('file_put_contents') || !function_exists('fopen')) {
            $output->writeln('<info>环境不支持 file_put_contents 或 fopen等函数,系统无法写文件</info>');
        } elseif (!get_extension_funcs('gd')) {
            $output->writeln('<info>环境不支持 gd 模块,图片水印和缩略生成功能无法使用</info>');
        } else {
            $output->writeln('<info>success!</info>');
        }
    }
}
