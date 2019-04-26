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
use app\library\Accesslog;
use app\library\Backup;
use app\library\Garbage;
use app\library\Sitemap;

class Monitor
{
    public function handle($request, Closure $next)
    {
        $this->illegalRequestLocking($request);
        $this->requestRelieve($request);

        $request->filter('safe_filter');    // 添加默认过滤方法
        $response = $next($request);
        $this->responseEnd($request);
        return $response;
    }

    /**
     * 缓解请求压力
     * 千分几率返回错误
     * @access private
     * @param
     * @return void
     */
    private function requestRelieve($request)
    {
        // 千分几率
        if (!in_array($request->controller(true), ['error', 'api']) && rand(1, 999) === 1) {
            Log::record('[并发]', 'alert')->save();
            die('<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><section><h2>500</h2><h3> Oops! Something went wrong.</h3></section>');

            $url = url('error');
            $response = Response::create($url, 'redirect', 302);
            throw new HttpResponseException($response);
        }
    }

    /**
     * 非法请求锁定
     * 锁定时间一天
     * @access private
     * @param
     * @return void
     */
    private function illegalRequestLocking($request)
    {
        clearstatcache();
        $request_log = app()->getRuntimePath() . 'concurrent' . DIRECTORY_SEPARATOR;
        if (!is_dir($request_log)) {
            chmod(app()->getRuntimePath(), 0777);
            mkdir($request_log, 0777, true);
        }
        $request_log .= md5(__DIR__ . $request->ip() . date('Ymd')) . '.php';

        // 锁定频繁请求IP
        if (is_file($request_log . '.lock')) {
            Log::record('[锁定]', 'alert')->save();
            die('<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><section><h2>500</h2><h3> Oops! Something went wrong.</h3></section>');
        }

        $time = date('YmdHi');

        if (is_file($request_log)) {
            include $request_log;
            if (!empty($number[$time]) && $number[$time] >= rand(20, 30)) {
                file_put_contents($request_log . '.lock', date('YmdHis'));
            }
        } else {
            $number = [$time => 1];
        }

        // 记录请求次数
        if (rand(1, 2) === 1) {
            $number[$time] = empty($number[$time]) ? 1 : ++$number[$time];
            $number = [$time => $number[$time]];
            file_put_contents($request_log, '<?php $number = ' . var_export($number, true) . ';');
        }
    }

    /**
     * 响应结束后执行方法
     * 访问日志
     * 垃圾文件清理
     * 自动备份数据库
     * 生成网站地图
     * @access private
     * @param
     * @return void
     */
    private function responseEnd($request)
    {
        if ($request->isGet() && !in_array($request->controller(true), ['admin', 'api', 'error'])) {
            (new Accesslog)->record();
        }

        if ($request->isGet() && $request->controller(true) == 'api' && rand(1, 10) === 1) {
            (new Garbage)->run();
            (new Backup)->auto();
            (new Sitemap)->save();
        }
    }
}
