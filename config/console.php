<?php

/**
 *
 * 控制台配置
 *
 * @package   NiPHP
 * @category  config
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    // 执行用户（Windows下无效）
    'user'     => null,
    // 指令定义
    'commands' => [
        'install' => \app\command\Install::class,
        'test' => \app\command\Test::class,
    ],
];
