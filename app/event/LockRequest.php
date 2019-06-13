<?php
/**
 *
 * 监听请求状态
 *
 * @package   NICMS
 * @category  app\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\event;

use think\App;
use think\facade\Log;

class LockRequest
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * log实例
     * @var \think\Log
     */
    protected $log;

    protected $request_log = '';

    public function handle(App $_app)
    {
        $this->app = $_app;
        $this->log = $this->app->log;

        $this->request_log = $this->app->getRuntimePath() . 'lock' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->request_log)) {
            chmod($this->app->getRuntimePath(), 0777);
            mkdir($this->request_log, 0777, true);
        }
        $this->request_log .= md5(client_mac() . date('Ymd')) . '.php';

        $this->lockRequest();
        $this->recordRequest();
        $this->concurrent();
    }

    /**
     * 记录请求数
     * @access protected
     * @param
     * @return void
     */
    protected function recordRequest(): void
    {
        // 以分钟统计请求量
        $time = (int)date('i');

        // 日志存在
        if (is_file($this->request_log) && $request_number = include($this->request_log)) {
            $request_number = !empty($request_number) ? (array)$request_number : [$time => 1];
            if (isset($request_number[$time]) && $request_number[$time] >= 50) {
                @file_put_contents($this->request_log . '.lock', date('Y-m-d H:i:s'));
            } else {
                $request_number[$time] = isset($request_number[$time]) ? ++$request_number[$time] : 1;
                $request_number = [$time => end($request_number)];
                @file_put_contents($this->request_log, '<?php /*' . client_mac() . '*/ return ' . var_export($request_number, true) . ';');
            }
        } else {
            $request_number = [$time => 1];
            @file_put_contents($this->request_log, '<?php /*' . client_mac() . '*/ return ' . var_export($request_number, true) . ';');
        }
    }

    /**
     * 锁定请求
     * @access protected
     * @param
     * @return void
     */
    protected function lockRequest(): void
    {
        // 锁定频繁请求IP
        if (is_file($this->request_log . '.lock') && filectime($this->request_log . '.lock') >= strtotime(date('Y-m-d'))) {
            $this->log->record('[锁定]', 'alert')->save();
            $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>502</title><section><h2>502</h2><h3>Oops! Something went wrong.</h3></section>';

            http_response_code(500);
            echo $error;
            exit();
        }
    }

    /**
     * 并发压力
     * @access protected
     * @param
     * @return void
     */
    protected function concurrent(): void
    {
        if ('api' !== $this->app->request->subDomain() && 1 === rand(1, 999)) {
            $this->log->record('[并发]', 'alert')->save();
            $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>500</title><section><h2>500</h2><h3>Oops! Something went wrong.</h3></section>';

            http_response_code(500);
            echo $error;
            exit();
        }
    }
}
