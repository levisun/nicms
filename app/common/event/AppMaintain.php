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
use app\common\library\UploadLog;

class AppMaintain
{

    public function handle()
    {
        $app_name = app('http')->getName();
        if ($app_name && 'api' !== $app_name) {
            // 数据库优化|修复
            (new DataManage)->optimize();

            // 数据库备份
            // (new DataManage)->autoBackup();

            only_execute('remove_garbage.lock', '-4 hour', function () {
                // 生成网站地图
                Sitemap::create();

                // 清除过期缓存文件
                ReGarbage::clear(runtime_path() . 'cache', 3);

                $uploads_path = public_path('storage' . DIRECTORY_SEPARATOR . 'uploads');

                // 清除游客上传的文件
                ReGarbage::clear($uploads_path . 'guest', 60);

                // 清除生成的缩略图
                ReGarbage::clear($uploads_path . 'thumb', 60);

                // 清除上传目录中的空目录
                ReGarbage::uploadEmptyDirectory();

                // 保证网站根目录整洁
                ReGarbage::publicDirTidy();

                // 清除上传垃圾文件
                UploadLog::clearGarbage();
            });
        }
    }
}
