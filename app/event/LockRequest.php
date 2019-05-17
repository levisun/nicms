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

use think\facade\Log;

class LockRequest
{

    protected $request_log = '';

    public function __construct()
    {
        $this->request_log = app()->getRuntimePath() . 'lock' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->request_log)) {
            chmod(app()->getRuntimePath(), 0777);
            mkdir($this->request_log, 0777, true);
        }
        $this->request_log .= md5(app()->request->ip() . app()->request->server('HTTP_USER_AGENT') . date('Ymd')) . '.php';
    }

    public function handle()
    {
        $this->lockRequest();
        $num = $this->recordRequest();
        $this->concurrent($num);
    }

    /**
     * 记录请求数
     * @access protected
     * @param
     * @return int
     */
    protected function recordRequest(): int
    {
        clearstatcache();
        $sum = 0;
        if (!app()->request->isOptions()) {
            if (is_file($this->request_log)) {
                include $this->request_log;
                $request_number = array_slice($request_number, -10, 10, true);
            }

            $time = app()->request->time();
            $request_number[$time] = isset($request_number[$time]) ? ++$request_number[$time] : 1;

            $sum = array_sum($request_number);
            if ($sum >= 35) {
                unlink($this->request_log);
                file_put_contents($this->request_log . '.lock', date('Y-m-d H:i:s'));
            } else {
                file_put_contents($this->request_log, '<?php $request_number=' . var_export($request_number, true) . ';');
            }
        }
        return $sum;
    }

    /**
     * 锁定请求
     * @access protected
     * @param
     * @return mixed
     */
    protected function lockRequest()
    {
        // 锁定频繁请求IP
        if (is_file($this->request_log . '.lock')) {
            Log::record('[锁定]', 'alert')->save();
            $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>502</title><section><h2>502</h2><h3>Oops! Something went wrong.</h3></section>';

            http_response_code(500);
            die($error);
        }
    }

    /**
     * 并发压力
     * @access protected
     * @param
     * @return mixed
     */
    protected function concurrent(int $_num)
    {
        if ('api' !== app()->request->subDomain() && 99 === rand($_num, 99)) {
            Log::record('[并发]', 'alert')->save();
            $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>500</title><section><h2>500</h2><h3>Oops! Something went wrong.</h3></section>';

            http_response_code(500);
            die($error);
        }
    }
}
