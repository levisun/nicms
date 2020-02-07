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
        $app_name = app('http')->getName();
        if ($app_name && 'api' !== $app_name) {
            // 生成网站地图
            (new Sitemap)->create();

            // 清除上传垃圾文件
            (new UploadFile)->ReGarbage();

            // 数据库优化|修复
            (new DataManage)->optimize();

            // 数据库备份
            // (new DataManage)->autoBackup();

            only_execute($app_name . '_remove_garbage.lock', '-4 hour', function () {
                Log::alert('[REGARBAGE] 应用维护');

                // 清除过期缓存文件
                $path = app('config')->get('cache.stores.' . app('config')->get('cache.default') . '.path') .
                        app('config')->get('cache.stores.' . app('config')->get('cache.default') . '.prefix');
                (new ReGarbage)->remove($path, 1);

                // 清除过期临时文件
                (new ReGarbage)->remove(app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp', 1);

                $this->reRootDirOrFile();
            });
        }
    }

    /**
     * 保证网站根目录整洁
     * @access private
     * @return void
     */
    private function reRootDirOrFile(): void
    {
        $keep = [
            '.', '..',
            'screen', 'static', 'storage', 'theme',
            '.htaccess', '.nginx', '.user.ini',
            '404.html', '502.html', 'favicon.ico',
            'index.php',
            'robots.txt', 'sitemap.xml',
        ];

        // 删除根目录多余文件
        $dir = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
        $files = is_dir($dir) ? scandir($dir) : [];
        foreach ($files as $dir_file) {
            // 跳过保留目录
            if (in_array($dir_file, $keep)) {
                continue;
            }

            if (is_dir($dir . $dir_file)) {
                (new ReGarbage)->remove($dir . $dir_file, 0);
                @rmdir($dir . $dir_file);
            } elseif (is_file($dir . $dir_file)) {
                Log::alert('[unlink] ' . $dir_file);
                @unlink($dir . $dir_file);
            }
        }

        // 删除screen目录多余文件
        $dir .= 'screen' . DIRECTORY_SEPARATOR;
        $files = is_dir($dir) ? scandir($dir) : [];
        foreach ($files as $dir_file) {
            // 跳过
            if (in_array($dir_file, ['.', '..', 'index.html'])) {
                continue;
            }
            // 跳过保留目录
            if (is_file($dir . $dir_file . DIRECTORY_SEPARATOR . '.keep.ini')) {
                continue;
            }

            if (is_dir($dir . $dir_file)) {
                (new ReGarbage)->remove($dir . $dir_file, 0);
                @rmdir($dir . $dir_file);
            } elseif (is_file($dir . $dir_file)) {
                Log::alert('[unlink] ' . $dir_file);
                @unlink($dir . $dir_file);
            }
        }
    }
}
