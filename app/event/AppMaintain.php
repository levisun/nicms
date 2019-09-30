<?php

/**
 *
 * 应用维护
 * 清除应用垃圾
 * 数据库维护
 *
 * @package   NICMS
 * @category  app\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\event;

use think\App;
use app\library\DataManage;
use app\library\ReGarbage;

class AppMaintain
{

    public function handle(App $_app)
    {
        // 垃圾信息维护
        $path = $_app->getRuntimePath() . 'lock' . DIRECTORY_SEPARATOR;
        is_dir($path) or mkdir($path, 0755, true);
        $lock = $path . 'remove_garbage.lock';

        clearstatcache();
        if (!is_file($lock) || filemtime($lock) <= strtotime('-1 hour')) {
            if ($fp = @fopen($lock, 'w+')) {
                if (flock($fp, LOCK_EX | LOCK_NB)) {
                    $_app->log->record('[REGARBAGE] 删除垃圾信息', 'alert');

                    $path = $_app->getRuntimePath();
                    $root_path = $_app->getRootPath() . 'public' . DIRECTORY_SEPARATOR;

                    (new ReGarbage)
                    // 清除过期缓存文件
                    ->remove($path . 'cache', 1)
                    // 清除过期日志文件
                    ->remove($path . 'log', 7)
                    // 清除过期临时文件
                    ->remove($path . 'temp', 1)
                    // 清除过期网站地图文件
                    ->remove($root_path . 'sitemaps', 1)
                    // 清除过期上传资料
                    ->remove($root_path . 'storage', 30);

                    fwrite($fp, '清除垃圾数据' . date('Y-m-d H:i:s'));
                    flock($fp, LOCK_UN);
                }
                fclose($fp);
            }
        }



        $path = $_app->getRuntimePath();
        if (true === $_app->isDebug()) {
            // 删除路由映射缓存
            // is_file($path . 'route.php') and unlink($path . 'route.php');
            // 删除数据表字段缓存
            // (bool) glob($path . 'schema' . DIRECTORY_SEPARATOR . '*') and
                // $_app->console->call('clear', ['schema']);
        } else {
            // 生成路由映射缓存
            is_file($path . 'route.php') or $_app->console->call('optimize:route');
            // 生成数据表字段缓存
            (bool) glob($path . 'schema' . DIRECTORY_SEPARATOR . '*') or
                $_app->console->call('optimize:schema');
        }


        // 数据库优化|修复
        (new DataManage)->optimize();
        // 数据库备份
        (new DataManage)->autoBackup();
    }
}
