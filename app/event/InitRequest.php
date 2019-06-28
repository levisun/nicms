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

class InitRequest
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

    /**
     * request实例
     * @var \think\Request
     */
    protected $request;

    protected $requestLog = '';

    public function handle(App $_app)
    {
        $this->app     = $_app;
        $this->log     = $this->app->log;
        $this->request = $this->app->request;

        $this->requestLog = $this->app->getRuntimePath() . 'lock' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->requestLog)) {
            chmod($this->app->getRuntimePath(), 0777);
            mkdir($this->requestLog, 0777, true);
        }
        $this->requestLog .= bindec($this->request->ip2bin($this->request->ip())). '.php';

        // 锁定频繁请求IP
        if (is_file($this->requestLog . '.lock')) {
            $this->log->record('[锁定]', 'alert')->save();
            $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>502</title><section><h2>502</h2><h3>Oops! Something went wrong.</h3></section>';

            http_response_code(502);
            echo $error;
            exit();
        } elseif ('api' !== $this->request->subDomain() && 1 === rand(1, 1999)) {
            $this->log->record('[并发]', 'alert')->save();
            $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>500</title><section><h2>500</h2><h3>Oops! Something went wrong.</h3></section>';

            http_response_code(500);
            echo $error;
            exit();
        }

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
        $number = is_file($this->requestLog) ? include $this->requestLog : '';

        // 非阻塞模式并发
        if ($fp = @fopen($this->requestLog, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $time = (int)date('i');     // 以分钟统计请求量
                $number = !empty($number) ? (array)$number : [$time => 1];
                if (isset($number[$time]) && $number[$time] >= 50) {
                    file_put_contents($this->requestLog . '.lock', date('Y-m-d H:i:s'));
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
