<?php
/**
 *
 * 维护
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

use Closure;
use think\Response;
use think\facade\Log;
use app\library\Accesslog;
use app\library\DataMaintenance;
use app\library\ReGarbage;
use app\library\Sitemap;

class Maintain
{

    public function handle()
    {
        (new Accesslog)->record();  // 生成访问日志
        (new Sitemap)->save();      // 生成网站地图

        if (1 === rand(1, 9)) {
            (new ReGarbage)->run();     // 清除过期缓存和日志等
        }
        
        if (date('ymd') % 10 == 0) {
            $lock = app()->getRuntimePath() . 'lock' . DIRECTORY_SEPARATOR . 'datamaintenance.lock';
            if (!is_file($lock)) {
                file_put_contents($lock, date('Y-m-d H:i:s'));
            }
            if (is_file($lock) && filemtime($lock) <= strtotime('-10 days')) {
                Log::record('[优化表 修复表]', 'alert')->save();
                ignore_user_abort(true);
                (new DataMaintenance)->optimize();  // 优化表
                (new DataMaintenance)->repair();    // 修复表
                ignore_user_abort(false);
            }
        }
    }
}
