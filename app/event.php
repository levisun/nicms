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
            \app\common\event\CheckRequestCache::class,
        ],
        'HttpRun' => [],
        'HttpEnd' => [
            \app\common\event\RecordRequestLog::class,
            \app\common\event\AppMaintain::class,
        ],
        'RouteLoaded' => [],
        'LogWrite' => [],
    ],
    'subscribe' => [],
];
