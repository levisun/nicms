<?php

/**
 *
 * 应用维护
 * 清除应用垃圾
 * 数据库维护
 *
 * @package   NICMS
 * @category  app\common\event
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\event;

use app\common\library\DataManage;
use app\common\library\ReGarbage;
use app\common\library\Sitemap;
use app\common\library\UploadFile;

class AppMaintain
{

    public function handle()
    {
        if ($lock = app('http')->getName()) {
            $lock .= '_remove_garbage.lock';

            // 生成网站地图
            (new Sitemap)->create();

            // 清除上传垃圾文件
            (new UploadFile)->ReGarbage();

            only_execute($lock, '-3 hour', function () {
                app('log')->record('[REGARBAGE] 应用维护', 'alert');

                // 清除过期缓存文件
                (new ReGarbage)->remove(app()->getRuntimePath() . 'cache', 1);

                // 清除过期临时文件
                (new ReGarbage)->remove(app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp', 1);
            });

            // 路由编译文件
            $path = app()->getRuntimePath();
            if (false === app()->isDebug() && !is_file($path . 'route.php')) {
                // app()->console->call('optimize:route', [app('http')->getName()]);
            } elseif (true === app()->isDebug() && is_file($path . 'route.php')) {
                unlink($path . 'route.php');
            }

            // 数据库优化|修复
            (new DataManage)->optimize();
            // 数据库备份
            // (new DataManage)->autoBackup();
        }
    }
}
