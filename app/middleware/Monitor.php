<?php
/**
 *
 *
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
use think\exception\HttpResponseException;
use think\facade\Log;
use think\facade\Request;
use app\library\Accesslog;
use app\library\Backup;
use app\library\Garbage;
use app\library\Sitemap;

class Monitor
{
    public function handle($request, Closure $next)
    {
        // 添加默认过滤方法
        $request->filter('safe_filter');

        clearstatcache();
        $request_log = app()->getRuntimePath() . 'concurrent' . DIRECTORY_SEPARATOR;
        if (!is_dir($request_log)) {
            chmod(app()->getRuntimePath(), 0777);
            mkdir($request_log, 0777, true);
        }
        $request_log .= md5(__DIR__ . Request::ip() . date('Ymd')) . '.php';

        // 频繁请求锁定IP
        if (is_file($request_log . '.lock')) {
            Log::record('[锁定]' . Request::ip(), 'alert')->save();
            die('<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><section><h2>500</h2><h3> Oops! Something went wrong.</h3></section>');
        }

        // 记录访问次数
        if (rand(1, 3) == 1) {
            $time = date('YmdHi');
            if (is_file($request_log)) {
                $result = include $request_log;
                if (!empty($result[$time]) && $result[$time] >= rand(20, 30)) {
                    file_put_contents($request_log . '.lock', date('YmdHis'));
                }
            } else {
                $result = [$time => 1];
            }
            if (is_array($result)) {
                $result[$time] = empty($result[$time]) ? 1 : $result[$time]+1;
                file_put_contents($request_log, '<?php return ' . var_export($result, true) . ';');
            }
        }

        // 千分几率跳转至错误页
        if (!in_array(Request::controller(true), ['error', 'api']) && rand(1, 999) === 1) {
            Log::record('[并发]' . Request::ip(), 'alert')->save();
            die('<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><section><h2>500</h2><h3> Oops! Something went wrong.</h3></section>');

            $url = url('error');
            $response = Response::create($url, 'redirect', 302);
            throw new HttpResponseException($response);
        }

        $response = $next($request);

        if (Request::isGet() && !in_array(Request::controller(true), ['admin', 'api', 'error'])) {
            (new Accesslog)->record();
        }

        if (Request::isGet() && Request::controller(true) == 'api' && rand(1, 10) === 1) {
            (new Garbage)->run();
            (new Backup)->auto();
            (new Sitemap)->save();
        }

        return $response;
    }
}
