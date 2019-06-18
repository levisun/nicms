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
            $result = $this->validate()->run();
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
            $result = $this->validate()->run();
            $this->setCache(false)->success($result['msg'], $result['data'], $result['code']);
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
            $result = $this->validate()->run();
            $this->setCache(false)->success($result['msg'], $result['data'], $result['code']);
        } else {
            $this->error('权限不足', 40006);
        }
    }

    /**
     * 短信接口
     * @access public
     * @param
     * @return void
     */
    public function sms(): void
    {
        if ($this->referer && $this->request->isPost() && $phone = $this->request->param('phone', false)) {
            $this->validate();
            $key = md5($this->request->ip() . client_mac());
            $has = session('sms_' . $key);
            if ($has && $has['time'] >= time()) {
                $this->setCache(false)->success('请勿重复请求');
            } else {
                $time = time() + 120;
                $captcha = rand(100000, 999999);
                session('sms_' . $key, ['phone' => $phone, 'time' => $time, 'captcha' => $captcha]);
                $this->setCache(false)->success('手机验证码发送成功');
            }
        } else {
            $this->error('权限不足', 40006);
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
            $this->validate();
            $ip = (new Ip)->info($ip);
            $this->setCache(true)->success('success', $ip);
        } else {
            $this->error('缺少参数', 40001);
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
            Download::file($file);
        } else {
            echo '错误请求';
            exit();
        }
    }
}
