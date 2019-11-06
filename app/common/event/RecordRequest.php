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

class RecordRequest
{

    public function handle()
    {
        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;
        is_dir($path) or mkdir($path, 0755, true);

        $client_ip = md5(app('request')->ip() . date('Ymd'));

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
                    $data = '<?php /*请求数 ' . app('request')->ip() . '*/ return ' . var_export($number, true) . ';';
                    fwrite($fp, $data);
                }
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }



        $request_params = app('request')->param()
            ? json_encode(app('request')->except(['username', 'password', 'sign']))
            : '';
        $request_url = app('request')->url(true);
        $request_method = app('request')->method(true) . ' ' . app('request')->ip();
        $run_time = number_format(microtime(true) - app()->getBeginTime(), 3);
        $time_memory = $run_time . 's ' .
            number_format((memory_get_usage() - app()->getBeginMem()) / 1024 / 1024, 3) . 'mb ';



        if (2 <= $run_time) {
            app('log')->record(
                '{长请求 ' . $time_memory . $request_method . ' ' . $request_url . '}' . PHP_EOL . $request_params,
                'info'
            );
        }



        $pattern = '/dist|base64_decode|call_user_func|chown|eval|exec|passthru|phpinfo|proc_open|popen|shell_exec/si';
        if (0 !== preg_match($pattern, $request_url . $request_params)) {
            app('log')->record(
                '{非法关键词 ' . $time_memory . $request_method . ' ' . $request_url . '}' . PHP_EOL . $request_params,
                'info'
            );
        }

        // (bool) glob($path . 'schema' . DIRECTORY_SEPARATOR . '*')
        // app()->console->call('optimize:schema', [app()->http->getName()]);
    }
}
