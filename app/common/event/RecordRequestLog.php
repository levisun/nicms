<?php

/**
 *
 * 请求记录
 *
 * @package   NICMS
 * @category  app\common\event
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\event;

use think\facade\Log;
use think\facade\Request;

class RecordRequestLog
{

    public function handle()
    {
        // 请求频繁创建锁定文件
        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;
        is_dir($path) or mkdir($path, 0755, true);

        $client_ip = md5(Request::ip());
        $log  = $path . $client_ip . '.php';
        $number = is_file($log) ? include $log : '';

        // 非阻塞模式并发
        if ($fp = @fopen($log, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $time = (int) date('dHi');   // 以分钟统计请求量
                $number = !empty($number) ? (array) $number : [$time => 1];
                if (isset($number[$time]) && $number[$time] >= 50) {
                    file_put_contents($path . $client_ip . '.lock', '请求锁定' . date('Y-m-d H:i:s'));
                } else {
                    $number[$time] = isset($number[$time]) ? ++$number[$time] : 1;
                    $number = [$time => end($number)];
                    $data = '<?php /*请求数 ' . Request::ip() . '*/ return ' . var_export($number, true) . ';';
                    fwrite($fp, $data);
                }
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }



        $this->recordLog();
    }

    /**
     * 请求日志
     * @return void
     */
    private function recordLog()
    {
        // $run_time = number_format(microtime(true) - app()->getBeginTime(), 3);
        $run_time = number_format(microtime(true) - Request::time(true), 3);
        $run_memory = number_format((memory_get_usage() - app()->getBeginMem()) / 1048576, 3) . 'mb ';
        $url = Request::ip() . ' ' . Request::method(true) . ' ' . Request::baseUrl(true);
        $params = Request::param()
            ? Request::except(['password', 'sign', '__token__', 'timestamp', 'sign_type', 'appid'])
            : [];
        $params = array_filter($params);
        $params = !empty($params) ? PHP_EOL . json_encode($params, JSON_UNESCAPED_UNICODE) : '';

        $log = '请求' . $run_time . 's, ' . $run_memory . $url . PHP_EOL;
        $log .= Request::server('HTTP_REFERER') ? '来源' . Request::server('HTTP_REFERER') . PHP_EOL : '';
        $log .= $params ? '参数' . trim(htmlspecialchars($params)) . PHP_EOL : '';

        $pattern = '/dist|base64_decode|call_user_func|chown|eval|exec|passthru|phpinfo|proc_open|popen|shell_exec|php/si';
        if (0 !== preg_match($pattern, $params)) {
            Log::warning('非法' . $log);
        } elseif (1 <= $run_time) {
            Log::warning('长' . $log);
        } else {
            Log::info($log);
        }
    }
}
