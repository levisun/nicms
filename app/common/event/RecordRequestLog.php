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

use think\facade\Request;
use app\common\library\AccessLog;

class RecordRequestLog
{

    public function handle()
    {
        // 请求频繁创建锁定文件
        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;
        is_dir($path) or mkdir($path, 0755, true);

        $client_ip = md5(Request::ip());
        $log = $path . $client_ip . '.php';
        $number = is_file($log) ? include $log : '';

        // 非阻塞模式并发
        if ($fp = @fopen($log, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $time = (int) date('dHi');   // 以分钟统计请求量
                $number = !empty($number) ? (array) $number : [$time => 1];
                if (isset($number[$time]) && $number[$time] > 55) {
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

        // 搜索蜘蛛日志
        AccessLog::spider();
        // AccessLog::api();
        AccessLog::log();
    }
}
