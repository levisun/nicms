<?php

/**
 *
 * 记录请求
 * 频繁或非法请求
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

class RecordRequest
{

    public function handle()
    {
        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;
        is_dir($path) or mkdir($path, 0755, true);

        $client_ip = md5(Request::ip() . date('Ymd'));

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



        $request_params = Request::param()
            ? Request::except(['username', 'password', 'sign', '__token__'])
            : [];
        $request_params = array_filter($request_params);
        $request_params = !empty($request_params) ? PHP_EOL . json_encode($request_params) : '';
        $request_url = Request::url(true);
        $request_method = Request::method(true) . ' ' . Request::ip();
        $run_time = number_format(microtime(true) - app()->getBeginTime(), 3);
        $time_memory = $run_time . 's ' .
            number_format((memory_get_usage() - app()->getBeginMem()) / 1024 / 1024, 3) . 'mb ';


        $tags = '访问';
        $pattern = '/dist|base64_decode|call_user_func|chown|eval|exec|passthru|phpinfo|proc_open|popen|shell_exec/si';
        if (0 !== preg_match($pattern, $request_url . $request_params)) {
            $tags = '<b style="color:red;">非法关键词</b>';
        } elseif (2 <= $run_time) {
            $tags = '<font style="color:red;">长请求</font>';
        }

        Log::record(
            '{' . $tags . $time_memory . $request_method . ' ' . $request_url . '}' . $request_params . PHP_EOL,
            'info'
        );
    }
}
