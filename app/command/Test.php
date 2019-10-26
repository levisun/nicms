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
            ->addOption('app', 'a', Option::VALUE_OPTIONAL, 'app name', 'www')
            // ->addOption('num', 'n', Option::VALUE_OPTIONAL, 'request num', 10)
            ->setDescription('the test command');
    }

    protected function execute(Input $input, Output $output)
    {

    }
}
