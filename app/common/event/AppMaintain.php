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

            $lock = $app_name . '_remove_garbage.lock';
            only_execute($lock, '-1 hour', function () {
                Log::record('[REGARBAGE] ' . app('http')->getName() . '应用维护', 'alert');

                // 清除过期缓存文件
                (new ReGarbage)->remove(app()->getRuntimePath() . 'cache', 1);

                // 清除过期临时文件
                (new ReGarbage)->remove(app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp', 1);

                // 删除根目录多余文件
                // $files = (array) glob(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . '*');
                // $cant = [
                //     '.htaccess',
                //     '.nginx',
                //     'favicon.ico',
                //     'index.php',
                //     'robots.txt',
                //     'router.php',
                //     'sitemap.xml',
                // ];
                // foreach ($files as $key => $file) {
                //     $file_name = pathinfo($file, PATHINFO_BASENAME);
                //     if (is_file($file) && !in_array($file_name, $cant)) {
                //         unlink($file);
                //     }
                // }
            });
        }
    }
}
