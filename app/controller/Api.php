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

use think\exception\HttpResponseException;
use app\library\Async;
use app\library\Download;
use app\library\Ip;

class Api extends Async
{

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
            $this->error('权限不足', 40006);
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
            $this->error('权限不足', 40006);
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
            $this->error('权限不足', 40006);
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
        if ($this->request->isGet() && $file = $this->request->param('file', false)) {
            if ($response = (new Download)->file($file)) {
                throw new HttpResponseException($response);
            }
        } else {
            echo '缺少参数';
            exit();
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
            $this->cache(true)->success('success', $ip);
        } else {
            $this->error('缺少参数', 40001);
        }
    }
}
