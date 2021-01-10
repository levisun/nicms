<?php

/**
 *
 * 控制层
 * Api
 *
 * @package   NICMS
 * @category  app\api\controller
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\BaseApi;
use app\common\library\Filter;

class Wechat extends BaseApi
{

    public function jssdk()
    {
        $h5 = '<script src="https://res.wx.qq.com/open/js/jweixin-1.6.0.js" defer="defer"></script>';
        $h5 = '';

    }

    public function index()
    {
        $wechat = new \Wechat([
            'token' => 'aZbAEA404q12CbZac4db9ne2fa9c12cd',                      // 填写你设定的key
            'encodingaeskey' => 'PFG9wG8bgmb5hAB8gmFpbXFGFB5z28WW5U5pA8bl8GF',  // 填写加密用的EncodingAESKey
            'appid' => 'wxcb66fe334d6c7e0c',                                    // 填写高级调用功能的app id
            'appsecret' => 'cdfec9d36594f85282cf3bf3fa7f4eed'                   // 填写高级调用功能的密钥
        ]);

        // $wechat->valid();

        $user = [
            'type'     => $wechat->getRev()->getRevType(),                    // 请求类型
            'event'    => $wechat->getRevEvent(),                             // 请求事件类型
            'formUser' => $wechat->getRevFrom(),                              // 请求用户
            'userData' => $wechat->getUserInfo($wechat->getRevFrom()),          // 用户个人信息
            'key'      => [
                'sceneId'       => Filter::strict($wechat->getRevSceneId()),      // 扫公众号二维码返回值
                'eventLocation' => Filter::strict($wechat->getRevEventGeo()),     // 获得的地理信息
                'text'          => Filter::strict($wechat->getRevContent()),      // 文字信息
                'image'         => Filter::strict($wechat->getRevPic()),          // 图片信息
                'location'      => Filter::strict($wechat->getRevGeo()),          // 地理信息
                'link'          => Filter::strict($wechat->getRevLink()),         // 链接信息
                'voice'         => Filter::strict($wechat->getRevVoice()),        // 音频信息
                'video'         => Filter::strict($wechat->getRevVideo()),        // 视频信息
                'result'        => Filter::strict($wechat->getRevResult())        // 群发或模板信息回复内容
            ],
        ];

        if ($user['type'] === \Wechat::MSGTYPE_EVENT) { }
    }
}
