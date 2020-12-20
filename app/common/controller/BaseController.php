<?php

/**
 *
 * 控制层基类
 *
 * @package   NICMS
 * @category  app\common\controller
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\controller;

use think\App;
use think\Response;
use think\exception\HttpResponseException;

abstract class BaseController
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
     * Cookie实例
     * @var \think\Cookie
     */
    protected $cookie;

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
     * 模板实例化方法
     * @var object
     */
    protected $view = null;

    /**
     * 权限认证KEY
     * @var string
     */
    protected $authKey = 'user_auth_key';

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     * @return void
     */
    public function __construct(App $_app)
    {
        $this->app     = &$_app;
        $this->config  = &$this->app->config;
        $this->cookie  = &$this->app->cookie;
        $this->request = &$this->app->request;
        $this->session = &$this->app->session;
        $this->view    = &$this->app->view;

        // 加载语言包
        $lang = root_path('app/common/lang') . $this->app->lang->getLangSet() . '.php';
        $this->app->lang->load($lang);

        // 调试模式,请到.env进行设置
        // api和logic层默认关闭
        $this->app->debug((bool) $this->app->env->get('app_debug', false));
        // 设置请求默认过滤方法
        $this->request->filter('\app\common\library\Filter::safe');

        // 请勿更改参数(超时,执行内存)
        @ignore_user_abort(false);
        @set_time_limit(60);
        @ini_set('max_execution_time', '60');
        @ini_set('memory_limit', '16M');

        // 控制器初始化
        $this->initialize();
    }

    public function __destruct()
    {
        ignore_user_abort(false);
    }

    // 初始化
    protected function initialize()
    {
    }

    /**
     * 重指向
     * @access protected
     * @param  string $_route 路由
     * @return void
     */
    protected function redirect(string $_route, int $_code = 302): void
    {
        // 临时跳转时记录当前地址
        if (302 === $_code) {
            $this->session->set('return_url', $this->request->url());
        }

        $response = Response::create(url($_route), 'redirect', $_code);
        throw new HttpResponseException($response);
    }

    /**
     * 渲染模板文件
     * @access protected
     * @param  string $_template 模板文件
     * @param  array  $_data     模板变量
     * @return string
     */
    protected function assign($name, $value = null)
    {
        return $this->view->assign($name, $value);
    }

    /**
     * 渲染模板文件
     * @access protected
     * @param  string $_template 模板文件
     * @param  array  $_data     模板变量
     * @return string
     */
    protected function fetch(string $_template, array $_data = []): string
    {
        return $this->view->assign($_data)->fetch($_template);
    }
}
