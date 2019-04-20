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
        clearstatcache();
        $request_log = app()->getRuntimePath() . 'concurrent' . DIRECTORY_SEPARATOR;
        if (!is_dir($request_log)) {
            chmod(app()->getRuntimePath(), 0777);
            mkdir($request_log, 0777, true);
        }
        $request_log .= md5(__DIR__ . Request::ip() . date('Ymd')) . '.php';

        // 频繁请求锁定IP
        if (is_file($request_log . '.lock')) {
            die('请求错误！请稍后再试～');
            die('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:( </h1><p> 500<br/><span style="font-size:30px">123</span></p></div>');
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

        // 千分几率跳转至并发错误页
        if (rand(1, 999) === 1 && !in_array(Request::controller(true), ['error', 'api'])) {
            Log::record('[并发]' . Request::ip(), 'alert');
            $url = url('error');
            $response = Response::create($url, 'redirect', 302);
            throw new HttpResponseException($response);
        }

        $response = $next($request);

        if (Request::isGet() && !in_array(Request::controller(true), ['admin', 'api'])) {
            (new Accesslog)->record();
        }

        if (Request::isGet() && !in_array(Request::controller(true), ['api'])) {
            // 减少频繁操作
            if (rand(1, 20) === 1) {
                (new Garbage)->remove();
                (new Sitemap)->save();
                (new Backup)->auto();
            }
        }

        return $response;
    }
}
