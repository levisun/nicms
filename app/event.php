<?php

/**
 *
 * 事件定义文件
 *
 * @package   NICMS
 * @category  app
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    'bind'      => [],
    'listen'    => [
        'AppInit'  => [
            // 应用环境支持检查
            \app\event\AppInspect::class,
            // 检查请求,频繁或非法请求将被锁定
            \app\event\CheckRequest::class,
        ],
        'HttpRun'  => [
        ],
        'HttpEnd'  => [
            \app\event\RecordRequest::class,
            \app\event\AppMaintain::class,
        ],
        'RouteLoaded' => [],
        'LogWrite' => [],
    ],
    'subscribe' => [],
];
