<?php

/**
 *
 * API接口层
 * 基础方法
 *     $this->authenticate(__METHOD__, ?操作日志) 权限验证
 *     $this->upload() 上传方法
 *     $this->validate(验证器, ?数据) 验证方法
 *
 * @package   NICMS
 * @category  app\common\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\controller;

use think\App;
use think\Response;
use think\exception\HttpResponseException;
use app\common\library\Base64;

abstract class BaseController
{

    /**
     * 模板实例化方法
     * @var object
     */
    protected $view = null;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * Config实例
     * @var \think\Config
     */
    protected $config;

    /**
     * request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * Session实例
     * @var \think\Session
     */
    protected $session;

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     * @return void
     */
    public function __construct(App $_app)
    {
        $this->app      = $_app;
        $this->config   = $this->app->config;
        $this->request  = $this->app->request;
        $this->session  = $this->app->session;
        $this->cookie   = $this->app->cookie;
        $this->view     = $this->app->view;

        $this->app->debug($this->config->get('app.debug'));
        $this->request->filter('\app\common\library\DataFilter::default');

        if (!$this->session->has('client_token')) {
            $this->session->set('client_token', Base64::client_id());
        }
        if (!$this->cookie->has('PHPSESSID') || $this->cookie->get('PHPSESSID') != $this->session->get('client_token')) {
            $this->cookie->set('PHPSESSID', $this->session->get('client_token'));
        }

        @ini_set('memory_limit', '8M');
        set_time_limit(30);

        if (1 === mt_rand(1, 999)) {
            $this->app->log->record('[并发]', 'alert')->save();
            http_response_code(500);
            echo '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>500</title><section><h2>500</h2><h3>Oops! Something went wrong.</h3></section><script>setTimeout(function(){location.href="/";}, 3000);</script>';
            exit();
        }

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    { }

    /**
     * miss
     * @access public
     * @param
     * @return void
     */
    public function miss(string $code = '404')
    {
        $assign = [
            'url' => $this->request->url(true),
        ];

        $code = $code ?: '404';
        return $this->fetch($code, $assign);
    }

    /**
     * 302重指向
     * @access public
     * @param  string $_route 路由
     * @return void
     */
    public function redirect(string $_route)
    {
        $response = Response::create(url($_route), 'redirect', 302);
        throw new HttpResponseException($response);
    }

    /**
     * 渲染模板文件
     * @access public
     * @param  string $_template 模板文件
     * @param  array  $_data     模板变量
     * @return void
     */
    public function fetch(string $_template, array $_data = [])
    {
        return $this->view->assign($_data)->fetch($_template);
    }
}
