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
use app\common\library\Base64;
use app\common\library\Ipinfo;
use think\captcha\facade\Captcha;

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

        // 加载语言包
        $lang  = $this->app->getBasePath() . 'common' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR;
        $lang .= $this->app->lang->getLangSet() . '.php';
        $this->app->lang->load($lang);

        // 调试模式,请到.env进行设置
        // api和logic层默认关闭
        $this->app->debug((bool) $this->app->env->get('app_debug', false));
        // 设置请求默认过滤方法
        $this->request->filter('\app\common\library\DataFilter::filter');

        // 生成客户端cookie令牌
        if (!$this->session->has('client_token')) {
            $this->session->set('client_token', Base64::client_id());
        }
        if (!$this->cookie->has('client_ip')) {
            $ip = (new Ipinfo)->get($this->request->ip());
            $this->cookie->set('client_ip', implode(',', $ip));
        }
        if (!$this->cookie->has('PHPSESSID')) {
            $this->cookie->set('PHPSESSID', $this->session->get('client_token'));
        }

        @ini_set('memory_limit', '8M');
        set_time_limit(30);

        if (1 === mt_rand(1, 999)) {
            $this->app->log->record('[并发]', 'alert')->save();
            http_response_code(500);
            echo '<style type="text/css">*{padding:0; margin:0;}body{background:#fff;font-family:"Century Gothic","Microsoft yahei";color:#333;font-size:18px;}section{text-align:center;margin-top:50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>500</title><section><h2>500</h2><h3>Oops! Something went wrong.</h3></section><script>setTimeout(function(){location.href="/";}, 3000);</script>';
            exit();
        }

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    { }

    /**
     * 验证码
     * @access public
     * @return mixed
     */
    public function verify()
    {
        $config = mt_rand(0, 1) ? 'verify_zh' : 'verify_math';
        return Captcha::create($config);
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
    protected function fetch(string $_template, array $_data = []): string
    {
        return $this->view->assign($_data)->fetch($_template);
    }
}
