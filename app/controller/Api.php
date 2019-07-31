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

declare(strict_types=1);

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
            $this->error('错误请求', 40009);
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
            $this->openCache(false)->success($result['msg'], $result['data'], $result['code']);
        } else {
            $this->error('错误请求', 40009);
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
            $this->openCache(false)->success($result['msg'], $result['data'], $result['code']);
        } else {
            $this->error('错误请求', 40009);
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
        if ($this->request->isPost() && $phone = $this->request->param('phone', false)) {
            if (preg_match('/^1[3-9][0-9]\d{8}$/', $phone)) {
                $this->validate();

                $key = $this->cookie->has('__uid') ? $this->cookie->get('__uid') : $this->request->ip();
                $key = md5('sms_' . $key);

                if ($this->session->has($key) && $result = $this->session->get($key)) {
                    if ($result['time'] >= time()) {
                        $this->openCache(false)->success('请勿重复请求');
                    }
                }

                $result = [
                    'captcha' => mt_rand(100000, 999999),
                    'time'    => time() + 120,
                    'phone'   => $phone,
                ];
                $this->session->set($key, $result);
                $this->openCache(false)->success('验证码发送成功');
            } else {
                $this->error('非法参数', 40002);
            }
        } else {
            $this->error('错误请求', 40009);
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
        if ($ip = $this->request->param('ip', false)) {
            if (false !== filter_var($ip, FILTER_VALIDATE_IP)) {
                $this->validate();
                $ip = Ip::info($ip);
                $this->openCache(true)->success('IP INFO', $ip);
            }
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

    /**
     * 微信接口
     * @access public
     * @param
     * @return void
     */
    public function wechat(): void
    {
        $wobj = new \Wechat([
            'token' => 'aZbAEA404q12CbZac4db9ne2fa9c12cd',                      // 填写你设定的key
            'encodingaeskey' => 'PFG9wG8bgmb5hAB8gmFpbXFGFB5z28WW5U5pA8bl8GF',  // 填写加密用的EncodingAESKey
            'appid' => 'wxcb66fe334d6c7e0c',                                    // 填写高级调用功能的app id
            'appsecret' => 'cdfec9d36594f85282cf3bf3fa7f4eed'                   // 填写高级调用功能的密钥
        ]);

        // $wobj->valid();

        $user = [
            'type'     => $wobj->getRev()->getRevType(),                    // 请求类型
            'event'    => $wobj->getRevEvent(),                             // 请求事件类型
            'formUser' => $wobj->getRevFrom(),                              // 请求用户
            'userData' => $wobj->getUserInfo($wobj->getRevFrom()),          // 用户个人信息
            'key'      => [
                'sceneId'       => default_filter($wobj->getRevSceneId()),      // 扫公众号二维码返回值
                'eventLocation' => default_filter($wobj->getRevEventGeo()),     // 获得的地理信息
                'text'          => default_filter($wobj->getRevContent()),      // 文字信息
                'image'         => default_filter($wobj->getRevPic()),          // 图片信息
                'location'      => default_filter($wobj->getRevGeo()),          // 地理信息
                'link'          => default_filter($wobj->getRevLink()),         // 链接信息
                'voice'         => default_filter($wobj->getRevVoice()),        // 音频信息
                'video'         => default_filter($wobj->getRevVideo()),        // 视频信息
                'result'        => default_filter($wobj->getRevResult())        // 群发或模板信息回复内容
            ],
        ];

        if ($user['type'] === \Wechat::MSGTYPE_EVENT) {

        }
    }
}
