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

use think\App;

class RecordRequest
{

    public function handle(App $_app)
    {
        $path = $_app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;
        is_dir($path) or mkdir($path, 0755, true);

        $client_ip = md5($_app->request->ip() . date('Ymd'));

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
                    $data = '<?php /*请求数 ' . $_app->request->ip() . '*/ return ' . var_export($number, true) . ';';
                    fwrite($fp, $data);
                }
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }



        $request_params = $_app->request->param()
            ? json_encode($_app->request->except(['username', 'password', 'sign']))
            : '';
        $request_url = $_app->request->url(true);
        $request_method = $_app->request->method(true) . ' ' . $_app->request->ip();
        $run_time = number_format(microtime(true) - $_app->getBeginTime(), 3);
        $time_memory = $run_time . 's ' .
            number_format((memory_get_usage() - $_app->getBeginMem()) / 1024 / 1024, 3) . 'mb ';



        if (1 <= $run_time) {
            $_app->log->record(
                '{长请求 ' . $time_memory . $request_method . ' ' . $request_url . '}' .
                    PHP_EOL . $request_params .
                    PHP_EOL,
                'info'
            );
        }



        $pattern = '/dist|base64_decode|call_user_func|chown|eval|exec|passthru|phpinfo|proc_open|popen|shell_exec/si';
        if (0 !== preg_match($pattern, $request_url . $request_params)) {
            $_app->log->record(
                '{非法关键词 ' . $time_memory . $request_method . ' ' . $request_url . '}' .
                    PHP_EOL . $request_params .
                    PHP_EOL,
                'info'
            );
        }



        1 === mt_rand(1, 19) and $_app->log->record(
            '{' . $time_memory . $request_method . ' ' . $request_url . '}' .
                PHP_EOL . $request_params .
                PHP_EOL,
            'info'
        );



        $path = $_app->getRuntimePath();
        if (false === $_app->config->get('app.debug')) {
            is_file($path . 'route.php') or $_app->console->call('optimize:route', [$_app->http->getName()]);
        } else {
            is_file($path . 'route.php') and unlink($path . 'route.php');
        }

        // (bool) glob($path . 'schema' . DIRECTORY_SEPARATOR . '*')
        // $_app->console->call('optimize:schema', [$_app->http->getName()]);
    }
}
