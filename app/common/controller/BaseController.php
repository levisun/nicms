<?php

/**
 *
 * 控制层基类
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
use app\common\library\Siteinfo;

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
        $lang  = $this->app->getBasePath() . 'common' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR;
        $lang .= $this->app->lang->getLangSet() . '.php';
        $this->app->lang->load($lang);

        // 调试模式,请到.env进行设置
        // api和logic层默认关闭
        $this->app->debug((bool) $this->app->env->get('app_debug', false));
        // 设置请求默认过滤方法
        $this->request->filter('\app\common\library\DataFilter::filter');

        @set_time_limit(10);
        @ini_set('max_execution_time', '10');
        @ini_set('memory_limit', '8M');

        $app_name = app('http')->getName();
        $result = Siteinfo::query($app_name);
        $this->view->config([
            'view_theme' => $result['theme'],
            'tpl_replace_string' => [
                '__NAME__'        => $result['name'],
                '__TITLE__'       => $result['title'],
                '__KEYWORDS__'    => $result['keywords'],
                '__DESCRIPTION__' => $result['description'],
                '__FOOTER_MSG__'  => $result['footer'],
                '__COPYRIGHT__'   => $result['copyright'],
                '__SCRIPT__'      => $result['script'],
            ]
        ]);

        $this->view->assign([
            'web_title' => $result['title'],
            'web_keywords' => $result['keywords'],
            'web_description' => $result['description'],
        ]);

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
    }

    /**
     * 302重指向
     * @access protected
     * @param  string $_route 路由
     * @return void
     */
    protected function redirect(string $_route): void
    {
        // 记录当前地址
        $this->session->set('return_url', $this->request->url());

        // 302
        $response = Response::create(url($_route), 'redirect', 302);
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
