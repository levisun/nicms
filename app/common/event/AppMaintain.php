<?php

/**
 *
 * 应用维护
 * 清除应用垃圾
 * 数据库维护
 *
 * @package   NICMS
 * @category  app\common\event
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\event;

use app\common\library\Sitemap;
use app\common\library\ClearGarbage;
use app\common\library\DataManage;
use app\common\library\UploadLog;

class AppMaintain
{

    public function handle()
    {
        $app_name = app('http')->getName();

        if ($app_name && 'cms' === $app_name) {
            $sitemap = public_path() . 'sitemap.xml';
            if (!is_file($sitemap) || strtotime('-3 hour') > filemtime($sitemap)) {
                Sitemap::xml();
                Sitemap::deadLink();
                Sitemap::robots();
            }
        }

        if ($app_name && 'api' !== $app_name) {
            // 数据库优化|修复
            (new DataManage)->optimize();

            $this->removeGarbage();
        }

        (new DataManage)->processList();
    }

    /**
     * 清除系统垃圾
     * @access private
     * @return void
     */
    private function removeGarbage(): void
    {
        only_execute('remove_garbage.lock', '-10 minutes', function () {
            // 清除过期无效缓存
            ClearGarbage::clearCache();

            // 清除游客上传的文件
            ClearGarbage::clear(public_path('storage/uploads/guest'), '-1 day');

            // 清除生成的缩略图
            ClearGarbage::clear(runtime_path('thumb'), '-1 day');

            // 清除临时文件
            ClearGarbage::clear(runtime_path('temp'), '-1 day');

            // 清除上传目录中的空目录
            ClearGarbage::uploadEmptyDirectory();

            // 保证网站根目录整洁
            ClearGarbage::publicDirTidy();

            // 清除上传垃圾文件
            UploadLog::clearGarbage();
        });
    }
}
