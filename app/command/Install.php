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
            ->addArgument('username', Argument::OPTIONAL, 'your username')
            ->addArgument('password', Argument::OPTIONAL, 'your password')
            ->setDescription('the install command');
    }

    protected function execute(Input $input, Output $output)
    {
        if (!$username = $input->getArgument('username')) {
            $output->writeln('username');
            return;
        }
        if (!$password = $input->getArgument('password')) {
            $output->writeln('<info>123</info>');
        }

        version_compare(PHP_VERSION, '7.1.0', '>=') or $output->writeln('系统需要PHP7.1+版本! 当前PHP版本:' . PHP_VERSION . '.');
        version_compare(app()->version(), '6.0.0RC6', '>=') or $output->writeln('系统需要ThinkPHP 6.0+版本! 当前ThinkPHP版本:' . app()->version() . '.');
        extension_loaded('pdo') or $output->writeln('请开启 pdo 模块!');
        extension_loaded('pdo_mysql') or $output->writeln('请开启 pdo_mysql 模块!');
        class_exists('ZipArchive') or $output->writeln('空间不支持 ZipArchive 方法,系统备份功能无法使用.');
        function_exists('file_put_contents') or $output->writeln('空间不支持 file_put_contents 函数,系统无法写文件.');
        function_exists('fopen') or $output->writeln('空间不支持 fopen 函数,系统无法读写文件.');
        get_extension_funcs('gd') or $output->writeln('空间不支持 gd 模块,图片水印和缩略生成功能无法使用.');


        // $password = trim($input->getArgument('password'));
        // $output->writeln("Hello," . $username . '!' . $password);


    }
}
