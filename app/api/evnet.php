<?php

/**
 *
 * 事件定义文件
 *
 * @package   NICMS
 * @category  app\api
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
            \app\common\event\CheckRequest::class,
        ],
        'HttpEnd'  => [
            \app\common\event\RecordRequest::class,
        ],
        'RouteLoaded' => [],
        'LogWrite' => [],
    ],
    'subscribe' => [],
];
