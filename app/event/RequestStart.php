<?php

/**
 *
 * 请求开始
 *
 * @package   NICMS
 * @category  app\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\event;

use think\App;

class RequestStart
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * ??
     */
    protected $path = '';
    protected $clientIp = '';

    public function handle(App $_app)
    {
        $this->app     = $_app;
        $this->request = $this->app->request;
        $this->path    = $this->app->getRuntimePath() . 'temp' . DIRECTORY_SEPARATOR;

        $this->clientIp = md5($this->request->ip() . date('Ymd'));

        $this->recordRequest();
    }


    /**
     * 记录请求数
     * @access protected
     * @param
     * @return void
     */
    protected function recordRequest(): void
    {
        $log = $this->path . $this->clientIp . '.php';
        $number = is_file($log) ? include $log : '';

        // 非阻塞模式并发
        if ($fp = @fopen($log, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $time = (int) date('dHi');   // 以分钟统计请求量
                $number = !empty($number) ? (array) $number : [$time => 1];
                if (isset($number[$time]) && $number[$time] >= 50) {
                    file_put_contents($this->path . $this->clientIp . '.lock', '请求锁定' . date('Y-m-d H:i:s'));
                } else {
                    $number[$time] = isset($number[$time]) ? ++$number[$time] : 1;
                    $number = [$time => end($number)];
                    $data = '<?php /*请求数 ' . $this->request->ip() . '*/ return ' . var_export($number, true) . ';';
                    fwrite($fp, $data);
                }
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    }
}
