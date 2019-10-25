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

use think\App;
use app\common\library\DataManage;
use app\common\library\ReGarbage;
use app\common\library\Ipinfo;

class AppMaintain
{

    public function handle(App $_app)
    {
        // 垃圾信息维护
        $path = $_app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'lock' . DIRECTORY_SEPARATOR;
        is_dir($path) or mkdir($path, 0755, true);
        $lock = $path . 'remove_garbage.lock';

        clearstatcache();
        if (!is_file($lock) || filemtime($lock) <= strtotime('-1 hour')) {
            if ($fp = @fopen($lock, 'w+')) {
                if (flock($fp, LOCK_EX | LOCK_NB)) {
                    $_app->log->record('[REGARBAGE] 删除垃圾信息', 'alert');

                    (new ReGarbage)
                    // 清除过期缓存文件
                    ->remove($_app->getRuntimePath() . 'cache', 1)
                    // 清除过期临时文件
                    ->remove($_app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp', 1)
                    // 清除过期SESSION
                    ->remove($_app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'session', 1)
                    // 清除过期网站地图文件
                    ->remove($_app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'sitemaps', 1);

                    fwrite($fp, '清除垃圾数据' . date('Y-m-d H:i:s'));
                    flock($fp, LOCK_UN);
                }
                fclose($fp);
            }
        }



        // 数据库优化|修复
        (new DataManage)->optimize();
        // 数据库备份
        (new DataManage)->autoBackup();
    }
}
