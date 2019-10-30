<?php

/**
 *
 * 控制层
 * Api
 *
 * @package   NICMS
 * @category  app\api\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\Async;

class Wechat extends Async
{

    public function index()
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

        if ($user['type'] === \Wechat::MSGTYPE_EVENT) { }
    }
}
