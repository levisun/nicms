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

use think\facade\Request;
use app\library\Async;
use app\library\Download;
use app\library\Ip;

class Api extends Async
{
    private $referer = false;

    /**
     * 构造方法
     * @access public
     * @param  string $_input_name
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->referer = Request::server('HTTP_REFERER') && Request::param('method', false);
    }

    /**
     * 查询接口
     * @access public
     * @param  string $module API分层名
     * @return Response
     */
    public function query(): void
    {
        if ($this->referer && Request::isGet()) {
            $this->run();
        } else {
            $this->error('query::request error');
        }
    }

    /**
     * 操作接口
     * @access public
     * @param  string $name API分层名
     * @return Response
     */
    public function handle(): void
    {
        if ($this->referer && Request::isPost()) {
            $this->run();
        } else {
            $this->error('handle::request error');
        }
    }

    /**
     * 上传接口
     * @access public
     * @param
     * @return Response
     */
    public function upload(): void
    {
        if (Request::isPost() && !empty($_FILES)) {
            $this->run();
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
        if (Request::isGet() && Request::param('file', false)) {
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
     * @return Response
     */
    public function ip(): void
    {
        if (Request::isGet() && $ip = Request::param('ip', false)) {
            $ip = (new Ip)->info($ip);
            $this->success('success', $ip);
        } else {
            $this->error('ip::request error');
        }
    }
}
