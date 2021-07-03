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

use app\common\library\Filter;

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

        // 调试模式,请到.env进行设置
        // api和logic层默认关闭
        $this->app->debug((bool) $this->app->env->get('app_debug', false));
        // 设置请求默认过滤方法
        $this->request->filter('\app\common\library\Filter::strict');

        // 请勿更改参数(超时,执行内存)
        @ignore_user_abort(false);
        @set_time_limit(60);
        @ini_set('max_execution_time', '60');
        @ini_set('memory_limit', '16M');

        // 应用维护
        if ($this->app->env->get('app_maintain', false)) {
            $this->maintain();
        }

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
     * 网站维护
     * @access protected
     * @param  string $_route 路由
     * @return void
     */
    protected function maintain(): void
    {
        $file = public_path('static') . 'maintain.html';
        $content = is_file($file)
            ? file_get_contents($file)
            : '';
        $response = Response::create($content, 'html', 200)
            ->allowCache(true)
            ->cacheControl('max-age=1440,must-revalidate')
            ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT')
            ->expires(gmdate('D, d M Y H:i:s', time() + 1440) . ' GMT');
        throw new HttpResponseException($response);
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

        $_route = 0 === strpos($_route, '//') ? $_route : url($_route);

        $response = Response::create($_route, 'redirect', $_code);
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
        $content = $this->view->assign($_data)->fetch($_template);

        $links = $this->parseLinks($content);
        $style = $this->parseStyle($content);
        $content = str_replace('</head>', $links . $style . '</head>', $content);

        $script = $this->parseScript($content);
        $files = $this->parseFiles($content);

        if (false !== strpos($content, '</body>')) {
            $content = str_replace('</body>', $files . $script . '</body>', $content);
        } else {
            $content .= $files . $script;
        }

        $content = Filter::space($content);

        $sys = [
            'runtime' => number_format(microtime(true) - $this->app->getBeginTime(), 3),
            'memory' => number_format((memory_get_usage() - $this->app->getBeginMem()) / 1048576, 3),
        ];

        return $content . '<!--' . date('Y-m-d H:i:s') . ', ' .
            $sys['runtime'] . ', ' .
            $sys['memory'] .
            '-->';
    }

    private function parseScript(string &$_content): string
    {
        $script = '';
        $pattern = '/<script( type=["\']+[^<>]+["\']+)?>(.*?)<\/script>/si';
        $_content = (string) preg_replace_callback($pattern, function ($matches) use (&$script) {
            $matches[2] = (string) preg_replace([
                '/[^:"\']\/\/ *.+\s+/i',
                '/\/\*.*?\*\//s',
            ], '', $matches[2]);
            $script .= trim($matches[2]);
            return;
        }, $_content);
        $script = preg_replace('/\s+/', ' ', $script);
        $script = preg_replace('/ *([:;,\{\}]+) +/', '$1', $script);

        return  $script ? '<script type="text/javascript">' . $script . '</script>' : '';
    }

    private function parseStyle(string &$_content): string
    {
        $style = '';
        $preg = '/<style( type=["\']+.*?["\']+)?>(.*?)<\/style>/si';
        $_content = (string) preg_replace_callback($preg, function ($matches) use (&$style) {
            $matches[2] = (string) preg_replace([
                '/[^:]\/\/ *.+\s+/i',
                '/\/\*.*?\*\//s',
            ], '', $matches[2]);
            $style .= trim($matches[2]);
            return;
        }, $_content);
        $style = preg_replace('/\s+/', ' ', $style);
        $style = preg_replace('/ *([:;,\{\}]+) +/', '$1', $style);

        return $style ? '<style type="text/css">' . $style . '</style>' : '';
    }

    private function parseFiles(string &$_content): string
    {
        $files = '';
        $pattern = '/<script[^<>]+><\/script>/si';
        $_content = (string) preg_replace_callback($pattern, function ($ele) use (&$files) {
            $files .= $ele[0];
            return;
        }, $_content);

        return $files;
    }

    private function parseLinks(string &$_content): string
    {
        $links = '';
        $preg = '/<link[^<>]+\/?>/si';
        $_content = (string) preg_replace_callback($preg, function ($ele) use (&$links) {
            $links .= $ele[0];
            return;
        }, $_content);

        return $links;
    }
}
