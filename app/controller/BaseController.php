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
        $this->cookie   = $this->app->cookie;
        $this->env      = $this->app->env;
        $this->lang     = $this->app->lang;
        $this->request  = $this->app->request;
        $this->response = $this->app->response;
        $this->session  = $this->app->session;

        $this->app->debug($this->config->get('app.debug'));
        $this->request->filter('default_filter');

        $this->view = Container::getInstance()->make('\app\library\Template');
        $this->view = Container::getInstance()->make('\app\library\View');
        $this->view->view_theme = 'default////';
        $this->view->fetch();
        die();

        $this->ipinfo = Ip::info($this->request->ip());
        Ip::info('125.' . mt_rand(1, 255) . '.' . mt_rand(1, 255) . '.' . mt_rand(1, 255));

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    { }

    /**
     * 302重指向
     * @access public
     * @param  string $_route 路由
     * @return void
     */
    public function redirect(string $_route)
    {
        $response = $this->response->create(url($_route), 'redirect', 302);
        throw new HttpResponseException($response);
    }

    /**
     * 渲染模板文件
     * @access public
     * @param  string $_template 模板文件
     * @param  array  $_data     模板变量
     * @return void
     */
    public function fetch(string $_template, array $_data = []): void
    {
        $this->view->fetch($_template, $_data);
    }

    /**
     * 设置模板变量
     * @access public
     * @param  array $_vars
     * @return void
     */
    public function assign(array $_vars = [])
    {
        $this->view->assign($_vars);
        return $this;
    }

    /**
     * 设置模板替换字符
     * @access public
     * @param  array $_replace
     * @return object
     */
    public function setReplace(array $_replace)
    {
        $this->view->setReplace($_replace);
        return $this;
    }

    /**
     * 设置模板主题
     * @access public
     * @param  string $_name
     * @return object
     */
    public function setTheme(string $_name)
    {
        $this->view->setTheme($_name);
        return $this;
    }
}
