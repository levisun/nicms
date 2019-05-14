<?php
/**
 *
 * 健康状态监控
 * 监听请求状态
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

class HealthMonitoring
{
    private $request_log = '';

    public function handle($request, Closure $next)
    {
        $request->filter('defalut_filter');

        $this->concurrent();
        $this->lockRequest($request);

        $response = $next($request);

        $this->writeRequestNumber($request);

        return $response;
    }

    /**
     * 记录请求数
     * @access private
     * @param
     * @return mixed
     */
    private function writeRequestNumber($request)
    {
        if (!$request->isOptions()) {
            $time = date('YmdHi');
            clearstatcache();
            if (is_file($this->request_log)) {
                include $this->request_log;
                if (!empty($number[$time]) && $number[$time] >= 60) {
                    file_put_contents($this->request_log . '.lock', date('YmdHis'));
                }
            } else {
                $number = [$time => 1];
            }

            // 记录请求次数
            $number[$time] = empty($number[$time]) ? 1 : ++$number[$time];
            $number = [$time => $number[$time]];
            file_put_contents($this->request_log, '<?php $number = ' . var_export($number, true) . ';');
        }
    }

    /**
     * 锁定请求
     * @access private
     * @param
     * @return mixed
     */
    private function lockRequest($request)
    {
        clearstatcache();
        $this->request_log = app()->getRuntimePath() . 'lock' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->request_log)) {
            chmod(app()->getRuntimePath(), 0777);
            mkdir($this->request_log, 0777, true);
        }
        $this->request_log .= md5(__DIR__ . $request->ip() . date('Ymd')) . '.php';

        // 锁定频繁请求IP
        if (is_file($this->request_log . '.lock')) {
            Log::record('[锁定]', 'alert')->save();
            $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><section><h2>502</h2><h3>Oops! Something went wrong.</h3></section>';

            return Response::create($error, '', 500);
        }
    }

    /**
     * 并发压力
     * @access private
     * @param
     * @return mixed
     */
    private function concurrent()
    {
        if (1 === rand(1, 999)) {
            Log::record('[并发]', 'alert')->save();
            $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><section><h2>500</h2><h3>Oops! Something went wrong.</h3></section>';

            return Response::create($error, '', 500);
        }
    }
}
