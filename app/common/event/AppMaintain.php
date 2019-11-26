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
use app\common\library\Sitemap;
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
        if (!is_file($lock) || filemtime($lock) <= strtotime('-3 hour')) {
            if ($fp = @fopen($lock, 'w+')) {
                if (flock($fp, LOCK_EX | LOCK_NB)) {
                    app('log')->record('[REGARBAGE] 应用维护', 'alert');

                    // 生成网站地图
                    (new Sitemap)->create();

                    // 清除上传垃圾文件
                    (new UploadFile)->ReGarbage();

                    // 清除过期缓存文件
                    (new ReGarbage)->remove(app()->getRuntimePath() . 'cache', 1);

                    // 清除过期临时文件
                    (new ReGarbage)->remove(app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp', 1);

                    fwrite($fp, '应用维护' . date('Y-m-d H:i:s'));
                    flock($fp, LOCK_UN);
                }
                fclose($fp);
            }
        }
    }

    public function test()
    {
        $path = app()->getRuntimePath();
        if (false === app()->isDebug()) {
            is_file($path . 'route.php') or app()->console->call('optimize:route', [app()->http->getName()]);
        } else {
            is_file($path . 'route.php') and unlink($path . 'route.php');
        }
    }
}
