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
 * @category  app\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\controller;

use think\App;
use think\Container;
use think\exception\HttpResponseException;
use app\library\Ip;

abstract class BaseController
{
    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        // Session初始化
        \think\middleware\SessionInit::class,
        // 页面Trace调试
        \think\middleware\TraceDebug::class,
    ];

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
     * Cookie实例
     * @var \think\Cookie
     */
    protected $cookie;

    /**
     * Env实例
     * @var \think\Env
     */
    protected $env;

    /**
     * Lang实例
     * @var \think\Lang
     */
    protected $lang;

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
        // $this->cookie   = $this->app->cookie;
        // $this->env      = $this->app->env;
        // $this->lang     = $this->app->lang;
        $this->request  = $this->app->request;
        // $this->response = $this->app->response;
        $this->session  = $this->app->session;

        $this->app->debug($this->config->get('app.debug'));
        $this->request->filter('default_filter');

        // $this->view = Container::getInstance()->make('\app\library\View');
        $this->view = $this->app->view;

        $this->ipinfo = Ip::info($this->request->ip());

        @ini_set('memory_limit', '8M');
        set_time_limit(60);

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
    public function miss(string $code = ''): void
    {
        if ('' === $code) {
            echo illegal_request();
            return ;
        }

        $assign = [
            'url' => $this->request->url(true),
            'param' => [
                'get' => $_GET,
                'post' => $_POST,
            ]
        ];

        $code = $code ?: '404';
        $this->view->fetch($code, $assign);
    }

    /**
     * 302重指向
     * @access public
     * @param  string $_route 路由
     * @return void
     */
    public function redirect(string $_route)
    {
        $response = $this->app->response->create(url($_route), 'redirect', 302);
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
        $vars = [
            'debug' => [
                'files'   => count(get_included_files()),
                'runtime' => number_format(microtime(true) - $this->app->getBeginTime(), 2) . 'S',
                'queries' => app('think\DbManager')->getQueryTimes(),
                'cache'   => $this->app->cache->getReadTimes() . ' reads,' . $this->app->cache->getWriteTimes() . ' writes',
                'mem'     => number_format((memory_get_usage() - $this->app->getBeginMem()) / 1024 / 1024, 2) . 'MB',
            ]
        ];
        $_data = array_merge($vars, $_data);
        return $this->view->filter(function($content){
            return preg_replace('/<\!\-\-.*?\-\->/si', '', $content);
        })->assign($_data)->fetch($_template);
    }
}
