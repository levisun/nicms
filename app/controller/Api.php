<?php
/**
 *
 * 控制层
 * Api
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
use think\facade\Config;
use think\facade\Request;
use app\library\Async;
use app\library\Download;
use app\library\Ip;

class Api extends Async
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var bool
     */
    protected $referer = false;

    /**
     * 构造方法
     * @access public
     * @param  string $_input_name
     * @return void
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        $this->app->debug(Config::get('app.debug'));

        $max_input_vars = (int)ini_get('max_input_vars');
        if (count($_POST) + count($_FILES) >= $max_input_vars - 5) {
            $this->error('[Async] request max params number error');
        }

        $this->referer = $this->request->server('HTTP_REFERER') && $this->request->param('method');

        header('X-Powered-By: NIAPI');
    }

    /**
     * 查询接口
     * @access public
     * @param  string $module API分层名
     * @return void
     */
    public function query(): void
    {
        if ($this->referer && $this->request->isGet()) {
            $result = $this->run();
            $this->success($result['msg'], $result['data'], $result['code']);
        } else {
            $this->error('query::request error');
        }
    }

    /**
     * 操作接口
     * @access public
     * @param  string $name API分层名
     * @return void
     */
    public function handle(): void
    {
        if ($this->referer && $this->request->isPost()) {
            $result = $this->run();
            $this->cache(false)->success($result['msg'], $result['data'], $result['code']);
        } else {
            $this->error('handle::request error');
        }
    }

    /**
     * 上传接口
     * @access public
     * @param
     * @return void
     */
    public function upload(): void
    {
        if ($this->referer && $this->request->isPost() && !empty($_FILES)) {
            $result = $this->run();
            $this->cache(false)->success($result['msg'], $result['data'], $result['code']);
        } else {
            $this->error('upload::request error');
        }
    }

    /**
     * 下载接口
     * @access public
     * @param
     * @return void
     */
    public function download(): void
    {
        if ($this->request->isGet() && $this->request->param('file', false)) {
            $response = (new Download)->file();
            throw new HttpResponseException($response);
        } else {
            die('download::request error');
        }
    }

    /**
     * IP地址信息接口
     * @access public
     * @param
     * @return void
     */
    public function ip(): void
    {
        if ($this->request->isGet() && $ip = $this->request->param('ip', false)) {
            $ip = (new Ip)->info($ip);
            $this->success('success', $ip);
        } else {
            $this->error('ip::request error');
        }
    }
}
