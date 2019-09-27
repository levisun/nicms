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
        'AppInit'  => [],
        'HttpRun'  => [
            // 检查请求,频繁或非法请求将被锁定
            \app\event\CheckRequest::class,
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
