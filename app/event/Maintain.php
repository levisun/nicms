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

use think\facade\Log;
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
            if (1 === rand(1, 9)) {
                (new ReGarbage)->run();     // 清除过期缓存和日志等
            }

            if (date('ymd') % 10 == 0) {
                $lock = app()->getRuntimePath() . 'lock' . DIRECTORY_SEPARATOR . 'datamaintenance.lock';
                if (!is_file($lock)) {
                    file_put_contents($lock, date('Y-m-d H:i:s'));
                }
                $date = (int)date('ymd') - 10;
                if (is_file($lock) && filemtime($lock) <= strtotime($date)) {
                    Log::record('[优化表 修复表]', 'alert')->save();
                    ignore_user_abort(true);
                    (new DataMaintenance)->optimize();  // 优化表
                    (new DataMaintenance)->repair();    // 修复表
                    ignore_user_abort(false);
                }
            }
        }
    }
}
