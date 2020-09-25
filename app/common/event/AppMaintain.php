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

use app\common\library\ClearGarbage;
use app\common\library\DataManage;
use app\common\library\Sitemap;
use app\common\library\UploadLog;

class AppMaintain
{

    public function handle()
    {
        $app_name = app('http')->getName();

        if ($app_name && 'cms' === $app_name) {
            // 生成网站地图
            $sitemap = public_path() . 'sitemap.xml';
            if (!is_file($sitemap) || strtotime('-3 hour') > filemtime($sitemap)) {
                Sitemap::create();
            }

            // 生成爬虫协议
            $robots = public_path() . 'robots.txt';
            if (!is_file($robots) || strtotime('-1 day') > filemtime($sitemap)) {
                $this->robots();
            }
        }

        if ($app_name && 'api' !== $app_name) {
            // 数据库优化|修复
            (new DataManage)->optimize();

            $this->removeGarbage();
        }
    }

    private function robots()
    {
        $robots = 'User-agent: *' . PHP_EOL;
        $paths = glob(public_path() . '*');
        if (!empty($paths)) {
            foreach ($paths as $dir) {
                if (is_dir($dir)) {
                    $robots .= 'Disallow: /' . pathinfo($dir, PATHINFO_BASENAME) . '/' . PHP_EOL;
                }
            }
        }
        $robots .= 'Disallow: *.do$' . PHP_EOL;
        $robots .= 'Allow: .html$' . PHP_EOL;
        $robots .= 'Sitemap: ' . request()->domain() . '/sitemap.xml' . PHP_EOL;

        file_put_contents(public_path() . 'robots.txt', $robots);
    }

    /**
     * 清除系统垃圾
     * @access private
     * @return void
     */
    private function removeGarbage(): void
    {
        only_execute('remove_garbage.lock', '-3 hour', function () {
            // 清除过期无效缓存
            ClearGarbage::clearCache();

            // 清除游客上传的文件
            ClearGarbage::clear(public_path('storage/uploads/guest'), '-60 day');

            // 清除生成的缩略图
            ClearGarbage::clear(public_path('storage/uploads/thumb'), '-60 day');

            // 清除上传目录中的空目录
            ClearGarbage::uploadEmptyDirectory();

            // 保证网站根目录整洁
            ClearGarbage::publicDirTidy();

            // 清除上传垃圾文件
            UploadLog::clearGarbage();
        });
    }
}
