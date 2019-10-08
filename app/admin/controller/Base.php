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

namespace app\admin\controller;

use think\App;
use think\exception\HttpResponseException;
use app\common\library\Accesslog;
use app\common\library\Ip;
use app\common\library\Sitemap;

abstract class Base
{
    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        // Session初始化
        \think\middleware\SessionInit::class,
        // 全局请求缓存
        \app\common\middleware\CheckRequestCache::class,
        // 页面Trace调试
        \think\middleware\TraceDebug::class,
        // 多语言加载
        \think\middleware\LoadLangPack::class,
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
        $this->view     = $this->app->view;

        $this->app->debug($this->config->get('app.debug'));
        $this->request->filter('default_filter');

        // 检查请求,频繁或非法请求将被锁定
        // $this->app->event->listen('HttpRun', \app\event\CheckRequest::class);
        // 记录请求
        // $this->app->event->listen('HttpEnd', \app\event\RecordRequest::class);
        // 应用性能维护
        // $this->app->event->listen('HttpEnd', \app\event\AppMaintain::class);

        @ini_set('memory_limit', '8M');
        set_time_limit(60);

        Ip::info($this->request->ip());

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
                // 'files'   => count(get_included_files()),
                'runtime' => number_format(microtime(true) - $this->app->getBeginTime(), 3) . 'S',
                // 'queries' => app('think\DbManager')->getQueryTimes(),
                // 'cache'   => $this->app->cache->getReadTimes() . ' reads,' . $this->app->cache->getWriteTimes() . ' writes',
                'mem'     => number_format((memory_get_usage() - $this->app->getBeginMem()) / 1024 / 1024, 3) . 'MB',
            ]
        ];
        $_data = array_merge($vars, $_data);
        return $this->view->assign($_data)->fetch($_template);
    }
}
