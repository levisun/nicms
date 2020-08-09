<?php

/**
 *
 * 异步请求实现
 *
 * @package   NICMS
 * @category  app\common\library\api
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library\api;

use think\App;
use think\Response;
use think\exception\HttpResponseException;

abstract class Base
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * Cache实例
     * @var \think\Cache
     */
    protected $cache;

    /**
     * Config实例
     * @var \think\Config
     */
    protected $config;

    /**
     * Lang实例
     * @var \think\Lang
     */
    protected $lang;

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

    /**
     * session实例
     * @var \think\Session
     */
    protected $session;

    /**
     * 构造方法
     * @access public
     * @return void
     */
    public function __construct(App $_app)
    {
        $this->app     = &$_app;
        $this->cache   = &$this->app->cache;
        $this->config  = &$this->app->config;
        $this->lang    = &$this->app->lang;
        $this->log     = &$this->app->log;
        $this->request = &$this->app->request;
        $this->session = &$this->app->session;

        // 请勿开启调试模式
        $this->app->debug(true);
        // 设置请求默认过滤方法
        $this->request->filter('\app\common\library\Filter::safe');
        // 请勿更改参数(超时,执行内存)
        @set_time_limit(10);
        @ini_set('max_execution_time', '10');
        @ini_set('memory_limit', '8M');

        $this->initialize();
    }

    public function __get(string $_name)
    {
        return $this->$_name;
    }

    public function __set(string $_name, string $_value)
    {
        $this->$_name = $_value;
    }

    /**
     * 初始化
     * @access protected
     * @return void
     */
    protected function initialize()
    {
    }

    /**
     * 抛出异常
     * @access protected
     * @param  string  $msg  提示信息
     * @param  integer $code 错误码，默认为40001
     * @return void
     */
    protected function abort(string $_msg, int $_code = 40001): void
    {
        $result = [
            'code'    => $_code,
            'message' => $_msg,
            'runtime' => number_format(microtime(true) - $this->app->getBeginTime(), 3) . ', ' .
                number_format((memory_get_usage() - $this->app->getBeginMem()) / 1048576, 3)
        ];

        if ($_code > 21000) {
            // 返回地址
            $result['return_url'] = $this->session->has('return_url')
                ? $this->session->pull('return_url')
                : '';

            // 新表单令牌
            $result['token'] = $this->request->isPost()
                ? $this->request->buildToken('__token__', 'md5')
                : '';
        }

        $result = array_filter($result);

        $response = Response::create($result, 'json');

        $this->log->warning('[Async] ' . $this->request->url());
        $this->log->save();
        // $this->session->save();

        ob_start('ob_gzhandler');

        throw new HttpResponseException($response);
    }
}
