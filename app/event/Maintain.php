<?php
/**
 *
 * 维护事件
 * 清除过期缓存和日志等
 * 生成网站地图
 * 数据库维护
 *
 * @package   NICMS
 * @category  app\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\event;

use think\Env;
use think\Request;
use app\library\Accesslog;
use app\library\DataMaintenance;
use app\library\ReGarbage;
use app\library\Sitemap;

class Maintain
{

    public function handle(Request $_request, Env $_env)
    {
        if (!in_array($_request->controller(true), [$_env->get('admin.entry'), 'api'])) {
            (new Accesslog)->record();      // 生成访问日志
            (new Sitemap)->create();        // 生成网站地图
        }

        if (!in_array($_request->controller(true), ['api'])) {
            // 优化修复数据库表
            clearstatcache();
            $lock = app()->getRuntimePath() . 'dmor.lock';
            $date = (string)(date('Ymd') - 10);
            if (0 === date('Ymd') % 10 && filemtime($lock) <= strtotime($date)) {
                $fp = @fopen($lock, 'w+');
                if ($fp && flock($fp, LOCK_EX | LOCK_NB)) {
                    ignore_user_abort(true);
                    (new DataMaintenance)->optimize();  // 优化表
                    (new DataMaintenance)->repair();    // 修复表
                    ignore_user_abort(false);

                    fwrite($fp, date('Y-m-d H:i:s'));
                    flock($fp, LOCK_UN);
                    fclose($fp);
                }
            }
            // 清除过期缓存和日志等
            elseif (1 === rand(1, 99)) {
                (new ReGarbage)->run();
            }
            // 自动备份数据库
            elseif (1 === rand(1, 99)) {
                (new DataMaintenance)->autoBackup();
            }
        }
    }
}
