<?php
/**
 *
 * 锁定非法请求
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

class LockingIllegalRequest
{

    public function handle($request, Closure $next)
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
            $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><section><h2>502</h2><h3>Oops! Something went wrong.</h3></section>';

            return Response::create($error, '', 500);
        }

        $response = $next($request);

        if (!$request->isOptions()) {
            $time = date('YmdHi');
            clearstatcache();
            if (is_file($request_log)) {
                include $request_log;
                if (!empty($number[$time]) && $number[$time] >= rand(25, 30)) {
                    file_put_contents($request_log . '.lock', date('YmdHis'));
                }
            } else {
                $number = [$time => 1];
            }

            // 记录请求次数
            $number[$time] = empty($number[$time]) ? 1 : ++$number[$time];
            $number = [$time => $number[$time]];
            file_put_contents($request_log, '<?php $number = ' . var_export($number, true) . ';');
        }

        return $response;
    }
}
