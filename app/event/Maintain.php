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

use think\facade\Request;
use app\library\Accesslog;
use app\library\DataMaintenance;
use app\library\ReGarbage;
use app\library\Sitemap;

class Maintain
{

    public function handle()
    {
        if ('www' === Request::subDomain()) {
            (new Accesslog)->record();      // 生成访问日志
            (new Sitemap)->create();        // 生成网站地图
        }

        if ('api' !== Request::subDomain()) {
            // 清除过期缓存和日志等
            if (1 === rand(1, 99)) {
                (new ReGarbage)->run();
            }
            // 自动备份数据库
            elseif (1 === rand(1, 199)) {
                (new DataMaintenance)->autoBackup();
            }
            // 优化修复数据库表
            elseif (0 === date('Ymd') % 10) {
                $lock = app()->getRuntimePath() . 'datamaintenance.lock';
                if (!is_file($lock)) {
                    file_put_contents($lock, date('Y-m-d H:i:s'));
                }
                $date = (string)(date('Ymd') - 10);
                clearstatcache();
                if (is_file($lock) && filemtime($lock) <= strtotime($date)) {
                    file_put_contents($lock, date('Y-m-d H:i:s'));
                    ignore_user_abort(true);
                    (new DataMaintenance)->optimize();  // 优化表
                    (new DataMaintenance)->repair();    // 修复表
                    ignore_user_abort(false);
                }
            }
        }
    }
}
