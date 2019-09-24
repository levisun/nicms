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
use app\library\Data;
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
        if (!is_file($lock) || filemtime($lock) <= strtotime('-1 days')) {
            if ($fp = @fopen($lock, 'w+')) {
                if (flock($fp, LOCK_EX | LOCK_NB)) {
                    $_app->log->record('[REGARBAGE] 删除垃圾信息', 'alert');

                    $garbage = new ReGarbage;

                    $path = $_app->getRuntimePath();

                    // 清除过期缓存文件
                    $garbage->remove($path . 'cache', 7);
                    // $this->remove($path . 'compile', 30);
                    // 清除过期日志文件
                    $garbage->remove($path . 'log', 7);
                    // 清除过期临时文件
                    $garbage->remove($path . 'temp', 3);

                    $path = $_app->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
                    // 清除过期网站地图文件
                    $garbage->remove($path . 'sitemaps', 1);
                    // 清除过期上传资料
                    $garbage->remove($path . 'storage', 30);

                    fwrite($fp, '清除垃圾数据' . date('Y-m-d H:i:s'));
                    flock($fp, LOCK_UN);
                }
                fclose($fp);
            }
        }


        $path = $_app->getRuntimePath();
        if (true === $_app->isDebug()) {
            // 删除路由映射缓存
            is_file($path . 'route.php') and $_app->console->call('clear', ['route']);
            // 删除数据表字段缓存
            (bool) glob($path . 'schema' . DIRECTORY_SEPARATOR . '*') and
                $_app->console->call('clear', ['schema']);
        } else {
            // 生成路由映射缓存
            is_file($path . 'route.php') or $_app->console->call('optimize:route');
            // 生成数据表字段缓存
            (bool) glob($path . 'schema' . DIRECTORY_SEPARATOR . '*') or
                $_app->console->call('optimize:schema');
        }

        // 数据库优化|修复
        1 === mt_rand(1, 9) and (new Data)->optimize();
        // 数据库备份
        1 === mt_rand(1, 9) and (new Data)->autoBackup();
    }
}
