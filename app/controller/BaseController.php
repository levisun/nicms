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

        $this->view = Container::getInstance()->make('\app\library\View');

        $this->ipinfo = Ip::info($this->request->ip());
        // Ip::info('125.' . mt_rand(1, 255) . '.' . mt_rand(1, 255) . '.' . mt_rand(1, 255));

        @ini_set('memory_limit', '16M');
        set_time_limit(30);

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
    public function miss(): void
    {
        // 组装请求参数
        $params = array_merge($_GET, $_POST, $_FILES);
        $params = !empty($params) ? json_encode($params) : '';
        $params = $this->request->url() . $params;
        $this->app->log->record('错误访问:' . $params, 'info');

        // 非法关键词
        // $pattern = '/dist|upload|base64_decode|call_user_func|chown|eval|exec|passthru|phpinfo|proc_open|popen|shell_exec|system|php|select|update|delete|insert|create/si';
        // if (false !== preg_match_all($pattern, $params, $matches) && 0 === count($matches[0])) {
        //     return true;
        // }

        $log = app()->getRuntimePath() . 'temp' . DIRECTORY_SEPARATOR . md5($this->request->ip() . date('YmdH')) . '.php';
        if (!is_dir(dirname($log))) {
            mkdir(dirname($log), 0755, true);
        }
        $number = is_file($log) ? include $log : '';

        // 非阻塞模式并发
        if ($fp = @fopen($log, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $time = (int) date('dH');   // 以分钟统计请求量
                $number = !empty($number) ? (array) $number : [$time => 1];
                if (isset($number[$time]) && $number[$time] >= 9) {
                    file_put_contents($log . '.lock', date('Y-m-d H:i:s'));
                } else {
                    $number[$time] = isset($number[$time]) ? ++$number[$time] : 1;
                    $number = [$time => end($number)];
                    $data = '<?php /*' . $this->request->ip() . '::error request*/ return ' . var_export($number, true) . ';';
                    fwrite($fp, $data);
                }
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }

        $assign = [
            'url' => $this->request->url()
        ];

        $this->view->fetch('404', $assign);
    }

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
}
