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
        @ini_set('memory_limit', '16M');
        set_time_limit(30); // ini_set('max_execution_time', '30');

        $this->app     = $_app;
        $this->cookie  = $this->app->cookie;
        $this->request = $this->app->request;

        if ('api' !== $this->request->subDomain() && 1 === mt_rand(1, 999)) {
            $this->app->log->record('[并发]', 'alert')->save();
            $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>500</title><section><h2>500</h2><h3>Oops! Something went wrong.</h3></section>';

            http_response_code(500);
            echo $error;
            exit();
        }

        // 客户端唯一ID
        if (!$this->cookie->has('__uid')) {
            $this->cookie->set('__uid', md5(uniqid(client_id(), true)));
        }

        $this->requestId = $this->cookie->has('__uid') ? $this->cookie->get('__uid') : $this->request->ip();
        $this->requestId = md5($this->requestId);

        // 检查空间环境支持
        $this->inspect();
        // 锁定频繁请求IP
        $this->lockRequest();
        // 记录请求数
        $this->recordRequest();
    }

    /**
     * 检查空间环境支持
     * @access protected
     * @param
     * @return void
     */
    protected function inspect(): void
    {
        $lock  = $this->app->getRuntimePath() . md5(__DIR__ . 'inspect lock') . '_inspect.lock';
        if (!is_file($lock)) {
            version_compare(PHP_VERSION, '7.1.0', '>=') or die('系统需要PHP7.1+版本! 当前PHP版本:' . PHP_VERSION . '.');
            extension_loaded('pdo') or die('请开启 pdo 模块!');
            extension_loaded('pdo_mysql') or die('请开启 pdo_mysql 模块!');
            function_exists('file_put_contents') or die('空间不支持 file_put_contents 函数,系统无法写文件.');
            function_exists('fopen') or die('空间不支持 fopen 函数,系统无法读写文件.');
            get_extension_funcs('gd') or die('空间不支持 gd 模块,图片打水印和缩略生成功能无法使用.');
            file_put_contents($lock, date('Y-m-d H:i:s'));
        }
    }

    /**
     * 锁定频繁请求IP
     * @access protected
     * @param
     * @return void
     */
    protected function lockRequest(): void
    {
        $log  = $this->app->getRuntimePath() . 'cache' . DIRECTORY_SEPARATOR;
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
        if (!is_dir($log)) {
            mkdir($log, 0777, true);
        }

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
