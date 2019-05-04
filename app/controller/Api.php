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
use think\facade\Session;
use app\library\Async;
use app\library\Base64;
use app\library\Download;
use app\library\Filter;
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
        $this->referer = Request::server('HTTP_REFERER') && Request::param('method', false);
    }

    /**
     * 查询接口
     * @access public
     * @param  string $module API分层名
     * @return Response
     */
    public function query(string $module = 'cms')
    {
        if ($this->referer && Request::isGet()) {
            $module = Filter::str($module);
            return $this->setModule($module)->run();
        } else {
            return $this->error('request error');
        }
    }

    /**
     * 操作接口
     * @access public
     * @param  string $name API分层名
     * @return Response
     */
    public function handle(string $module = 'cms')
    {
        if ($this->referer && Request::isPost()) {
            $module = Filter::str($module);
            return $this->setModule($module)->run();
        } else {
            return $this->error('request error');
        }
    }

    /**
     * 上传接口
     * @access public
     * @param
     * @return Response
     */
    public function upload(string $module = 'cms')
    {
        if ($this->referer && Request::isPost() && !empty($_FILES)) {
            $module = Filter::str($module);
            return $this->setModule($module)->run();
        } else {
            return $this->error('request error');
        }
    }

    /**
     * 下载接口
     * @access public
     * @param
     * @return Response
     */
    public function download()
    {
        if (Request::isGet() && Request::param('file', false)) {
            return (new Download)->file();
        } else {
            die('request error');
        }
    }

    /**
     * IP地址信息接口
     * @access public
     * @param
     * @return Response
     */
    public function ip()
    {
        if (Request::isGet() && $ip = Request::param('ip', false)) {
            $ip = (new Ip)->info($ip);
            return $this->success('success', $ip);
        } else {
            return $this->error('request error');
        }
    }
}
