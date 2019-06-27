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
declare (strict_types = 1);

namespace app\controller;

use think\App;
use think\Container;
use think\exception\HttpResponseException;
use app\library\Rbac;

abstract class BaseController
{
    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        'think\middleware\SessionInit'
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
        $this->lang     = $this->app->lang;
        $this->request  = $this->app->request;
        $this->response = $this->app->response;
        $this->session  = $this->app->session;

        $this->view = Container::getInstance()->make('\app\library\Template');

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    { }

    public function _404()
    {
        $response = $this->response->create(url('404'), 'redirect', 302);
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

    /**
     * 校验请求参数合法性
     * @access protected
     * @param  string $_str
     * @return void
     */
    protected function verification(string $_str): void
    {
        if (empty($_str) || preg_match('/[^A-Za-z]+/si', $_str)) {
            $response = $this->response->create(url('404'), 'redirect', 302);
            throw new HttpResponseException($response);
        }
    }

    /**
     * 操作验证权限
     * @access private
     * @param  string $_auth_key    认证ID
     * @param  string $_method      模块
     * @param  string $_logic       业务层
     * @param  string $_controller  控制器
     * @param  string $_action      方法
     * @return void
     */
    protected function authenticate(string $_auth_key, string $_method, string $_logic, string $_controller, string $_action): void
    {
        if (session('?' . $_auth_key)) {
            $result = (new Rbac)->authenticate(
                session($_auth_key),
                $_method,
                $_logic,
                $_controller,
                $_action
            );

            if (false === $result) {
                $url = url('settings/info/index');
            }
        } elseif (session('?' . $_auth_key) && $_logic === 'account') {
            $url = url('settings/info/index');
        } elseif (!session('?' . $_auth_key) && !in_array($_action, ['login', 'forget'])) {
            $url = url('account/user/login');
        }

        if (isset($url)) {
            $response = $this->response->create($url, 'redirect', 302);
            throw new HttpResponseException($response);
        }
    }
}
