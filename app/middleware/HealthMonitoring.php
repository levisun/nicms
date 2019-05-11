<?php
/**
 *
 * 健康状态监控
 * 清除过期缓存和日志等
 * 生成网站地图
 * 生成数据备份
 *
 * @package   NICMS
 * @category  app\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\middleware;

use Closure;
use think\Response;
use think\facade\Log;
use app\library\Accesslog;
use app\library\DataMaintenance;
use app\library\ReGarbage;
use app\library\Sitemap;

class HealthMonitoring
{

    public function handle($request, Closure $next)
    {
        if ('api' !== $request->subDomain() && 1 === rand(1, 999)) {
            Log::record('[并发]', 'alert')->save();
            $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><section><h2>500</h2><h3>Oops! Something went wrong.</h3></section>';

            return Response::create($error, '', 500);
        }

        if ('www' === $request->subDomain()) {
            (new Accesslog)->record();  // 生成访问日志
            (new Sitemap)->save();      // 生成网站地图
        }

        $response = $next($request);

        if ('api' !== $request->subDomain() && 1 === rand(1, 9)) {
            (new ReGarbage)->run();     // 清除过期缓存和日志等
        }

        if ('api' !== $request->subDomain() && date('ymd') % 10 == 0) {
            $lock = app()->getRuntimePath() . 'datamaintenance.lock';
            if (!is_file($lock) || filemtime($lock) <= strotime('-10 days')) {
                (new DataMaintenance)->optimize();  // 优化表
                (new DataMaintenance)->repair();    // 修复表
                file_put_contents($lock, date('Y-m-d H:i:s'));
            }
        }

        return $response;
    }
}
