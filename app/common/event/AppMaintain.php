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

use app\common\library\ReGarbage;
use app\common\library\UploadFile;

class AppMaintain
{

    public function handle()
    {
        // 垃圾信息维护
        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'lock' . DIRECTORY_SEPARATOR;
        is_dir($path) or mkdir($path, 0755, true);
        $lock = $path . app()->http->getName() . '_remove_garbage.lock';

        clearstatcache();
        if (!is_file($lock) || filemtime($lock) <= strtotime('-1 hour')) {
            if ($fp = @fopen($lock, 'w+')) {
                if (flock($fp, LOCK_EX | LOCK_NB)) {
                    app('log')->record('[REGARBAGE] 删除垃圾信息', 'alert');

                    // 清除上传垃圾文件
                    // (new UploadFile)->ReGarbage();

                    (new ReGarbage)
                        // 清除过期缓存文件
                        ->remove(app()->getRuntimePath() . 'cache', 1)
                        // 清除过期临时文件
                        ->remove(app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp', 2)
                        // 清除过期网站地图文件
                        ->remove(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'storage'. DIRECTORY_SEPARATOR . 'sitemaps', 3);

                    $path = app()->getRuntimePath();
                    if (false === app()->isDebug()) {
                        is_file($path . 'route.php') or app()->console->call('optimize:route', [app()->http->getName()]);
                    } else {
                        is_file($path . 'route.php') and unlink($path . 'route.php');
                    }

                    fwrite($fp, '清除垃圾数据' . date('Y-m-d H:i:s'));
                    flock($fp, LOCK_UN);
                }
                fclose($fp);
            }
        }
    }
}
