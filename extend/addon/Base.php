<?php

/**
 *
 * 插件
 *
 * @package   NICMS
 * @category  addon
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace addon;

use think\App;

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
     * 插件配置
     * @var array
     */
    protected $config = [];

    /**
     * 页面输出内容
     * @var string
     */
    protected $content = '';

    /**
     * 构造方法
     * @access public
     * @param  App    $_app     应用对象
     * @param  array  $_config  配置信息
     * @param  string $_content 页面输出内容
     * @return void
     */
    public function __construct(App $_app, array $_config, string $_content)
    {
        $this->app     = &$_app;
        $this->cookie  = &$this->app->cookie;
        $this->request = &$this->app->request;
        $this->session = &$this->app->session;
        $this->view    = &$this->app->view;

        $this->config = &$_config;
        $this->content = &$_content;
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public function run(): void
    {}

    /**
     * 追加内容
     * @access protected
     * @param  string $_str
     * @return string
     */
    protected function append(string &$_str): string
    {
        $pos = strripos($this->content, '</body>');
        if (false !== $pos) {
            $this->content = substr($this->content, 0, $pos) . $_str . substr($this->content, $pos);
        } else {
            $this->content = $this->content . $_str;
        }
        return $this->content;
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
