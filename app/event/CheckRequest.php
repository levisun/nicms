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

declare(strict_types=1);

namespace app\event;

use think\App;

class CheckRequest
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * Cookie实例
     * @var \think\Cookie
     */
    protected $cookie;

    protected $requestId;

    /**
     * request实例
     * @var \think\Request
     */
    protected $request;

    public function handle(App $_app)
    {
        $this->app     = $_app;
        $this->cookie  = $this->app->cookie;
        $this->request = $this->app->request;

        if ('api' !== $this->request->controller(true) && 1 === rand(1, 1999)) {
            $this->log->record('[并发]', 'alert')->save();
            $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>500</title><section><h2>500</h2><h3>Oops! Something went wrong.</h3></section>';

            http_response_code(500);
            echo $error;
            exit();
        }

        // 客户端唯一ID
        !$this->cookie->has('__uid') and $this->cookie->set('__uid', md5(uniqid(client_id(), true)));

        $this->requestId = $this->cookie->has('__uid') ? $this->cookie->get('__uid') : $this->request->ip();
        $this->requestId = md5($this->requestId);

        // 锁定频繁请求IP
        $this->lockRequest();
        // 记录请求数
        $this->recordRequest();
    }

    /**
     * 锁定频繁请求IP
     * @access protected
     * @param
     * @return void
     */
    protected function lockRequest(): void
    {
        $log  = $this->app->getRuntimePath() . 'req' . DIRECTORY_SEPARATOR;
        if (!is_dir($log)) {
            chmod($this->app->getRuntimePath(), 0777);
            mkdir($log, 0777, true);
        }
        $log .= $this->requestId . '.php.lock';

        if (is_file($log)) {
            $this->log->record('[锁定]', 'alert')->save();
            $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>502</title><section><h2>502</h2><h3>Oops! Something went wrong.</h3></section>';

            http_response_code(502);
            echo $error;
            exit();
        }
    }

    /**
     * 记录请求数
     * @access protected
     * @param
     * @return void
     */
    protected function recordRequest(): void
    {
        $log = $this->app->getRuntimePath() . 'req' . DIRECTORY_SEPARATOR;
        $log .= $this->requestId . '.php';

        $number = is_file($log) ? include $log : '';

        // 非阻塞模式并发
        if ($fp = @fopen($log, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $time = (int) date('Hi');     // 以分钟统计请求量
                $number = !empty($number) ? (array) $number : [$time => 1];
                if (isset($number[$time]) && $number[$time] >= 50) {
                    file_put_contents($log . '.lock', date('Y-m-d H:i:s'));
                } else {
                    $number[$time] = isset($number[$time]) ? ++$number[$time] : 1;
                    $number = [$time => end($number)];
                    $data = '<?php /*' . $this->request->ip() . '*/ return ' . var_export($number, true) . ';';
                    fwrite($fp, $data);
                }
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    }
}
