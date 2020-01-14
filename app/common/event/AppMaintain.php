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

use think\facade\Log;
use app\common\library\DataManage;
use app\common\library\ReGarbage;
use app\common\library\Sitemap;
use app\common\library\UploadFile;

class AppMaintain
{

    public function handle()
    {
        if ($app_name = app('http')->getName()) {
            if ('api' !== $app_name) {
                // 生成网站地图
                (new Sitemap)->create();

                // 清除上传垃圾文件
                (new UploadFile)->ReGarbage();

                // 数据库优化|修复
                (new DataManage)->optimize();

                // 数据库备份
                // (new DataManage)->autoBackup();
            }

            only_execute($app_name . '_remove_garbage.lock', '-4 hour', function () {
                Log::alert('[REGARBAGE] ' . app('http')->getName() . '应用维护');

                // 清除过期缓存文件
                (new ReGarbage)->remove(app()->getRuntimePath() . 'cache', 1);

                // 清除过期临时文件
                (new ReGarbage)->remove(app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp', 1);

                // 删除根目录多余文件
                $dir = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
                $files = is_dir($dir) ? scandir($dir) : [];
                foreach ($files as $file) {
                    if ('.' === $file || '..' === $file) {
                        continue;
                    } elseif (is_dir($dir . $file) && !in_array($file, ['screen', 'static', 'storage', 'theme'])) {
                        (new ReGarbage)->remove($dir . $file, 0);
                        @rmdir($dir . $file);
                    } elseif (is_file($dir . $file) && !in_array($file, ['.htaccess', '.nginx', '.user.ini', '404.html', '502.html', 'favicon.ico', 'index.php', 'robots.txt', 'sitemap.xml'])) {
                        @unlink($dir . $file);
                    }
                }
            });
        }
    }
}
