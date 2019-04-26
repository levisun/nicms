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
use app\library\Ip;

class Api extends Async
{
    private $referer = false;
    private $actionName = null;

    /**
     * 构造方法
     * @access public
     * @param  string $_input_name
     * @return void
     */
    public function __construct()
    {
        $this->referer = Request::server('HTTP_REFERER', false);
        $this->actionName = Request::param('method');
        list($this->actionName, $this->actionName, $this->actionName) = explode('.', $this->actionName);
    }

    /**
     * 查询接口
     * @access public
     * @param  string $module API分层名
     * @return void
     */
    public function query(string $module = 'cms')
    {
        if ($this->referer && Request::isGet() && $this->actionName == 'query') {
            $this->setModule($module)->run();
        } else {
            $this->error('request error');
        }
    }

    /**
     * 操作接口
     * @access public
     * @param  string $name API分层名
     * @return void
     */
    public function handle(string $module = 'cms')
    {
        if ($this->referer && Request::isPost()) {
            $this->setModule($module)->run();
        } else {
            $this->error('request error');
        }
    }

    /**
     * 上传接口
     * @access public
     * @param
     * @return void
     */
    public function upload(string $module = 'cms')
    {
        if ($this->referer && Request::isPost() && !empty($_FILES)) {
            $this->setModule($module)->run();
        } else {
            $this->error('request error');
        }
    }

    /**
     * IP地址信息接口
     * @access public
     * @param
     * @return void
     */
    public function ip()
    {
        if (Request::isGet() && $ip = Request::param('ip', false)) {
            $ip = (new Ip)->info($ip);
            $this->success('success', $ip);
        } else {
            $this->error('request error');
        }
    }

    /**
     * 下载接口
     * @access public
     * @param
     * @return void
     */
    public function download()
    {
        if (Request::isGet() && Request::param('file', false)) {
            (new Download)->file();
        } else {
            die('request error');
        }
    }
}
