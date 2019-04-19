<?php
/**
 *
 * 删除运行垃圾文件
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
use think\facade\Log;
use think\facade\Request;
use app\library\Accesslog;
use app\library\Backup;
use app\library\Garbage;
use app\library\Sitemap;

class Finish
{
    public function handle($request, Closure $next)
    {
        $request_file = app()->getRuntimePath() . 'concurrent' . DIRECTORY_SEPARATOR;
        if (!is_dir($request_file)) {
            chmod(app()->getRuntimePath(), 0777);
            mkdir($request_file, 0777, true);
        }
        $request_file .= md5(__DIR__ . Request::ip() . date('Ymd')) . '.php';

        // 锁定IP请求
        if (is_file($request_file . '.lock')) {
            Log::record('频繁请求锁定IP:' . Request::ip(), 'alert');
            $url = url('error');
            throw new \think\exception\HttpResponseException(redirect($url));
        }

        // 记录访问次数
        if (rand(1, 3) == 1) {
            $time = md5(Request::header('USER-AGENT') . date('YmdHi'));
            if (is_file($request_file)) {
                $result = include $request_file;
                if (!empty($data[$time]) && $data[$time] >= 20) {
                    file_put_contents($request_file . '.lock', date('YmdHis'));
                }
            } else {
                $result = [$time => 1];
            }
            if (is_array($result)) {
                $result[$time] = empty($result[$time]) ? 1 : $result[$time]+1;
                file_put_contents($request_file, '<?php return ' . var_export($result, true) . ';');
            }
        }

        // 千分几率跳转至并发错误页
        if (rand(1, 999) === 1 && !in_array(Request::controller(true), ['error', 'api'])) {
            Log::record('[并发]' . Request::ip(), 'alert');
            $url = url('error');
            throw new \think\exception\HttpResponseException(redirect($url));
        }

        $response = $next($request);

        if (Request::isGet() && !in_array(Request::subDomain(), ['admin', 'api', 'cdn'])) {
            (new Accesslog)->record();

            // 减少频繁操作
            if (rand(1, 10) === 1) {
                (new Garbage)->remove();
                (new Sitemap)->save();
                (new Backup)->auto();
            }
        }

        return $response;
    }
}
