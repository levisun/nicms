<?php

/**
 *
 * 备份
 *
 * @package   NICMS
 * @category  app\command
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2021
 */

declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

use app\common\library\DataManage;
use app\common\library\File;

class Backup extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('backup')
            ->addOption('type', null, Option::VALUE_OPTIONAL, 'site or database')
            ->setDescription('the backup command');
    }

    protected function execute(Input $input, Output $output)
    {
        if (!$input->hasOption('type')) {
            $output->writeln('Please enter the backup type. (site or database)');
        }

        // 删除老旧备份
        $glob = File::glob(runtime_path('backup'));
        while ($glob->valid()) {
            $filename = $glob->current();
            $glob->next();

            if (is_file($filename) && filemtime($filename) <= strtotime('-180 day')) {
                @unlink($filename);
            }
        }

        $type = $input->getOption('type');
        $type = strtolower($type);
        if ('site' == $type) {
            $result = (new DataManage)->site();
        } elseif ('database' == $type) {
            $result = (new DataManage)->backup();
        } else {
            $output->writeln('Please enter the backup type. (site or database)');
        }

        $output->writeln($result ? 'success' : 'fail');
    }
}
