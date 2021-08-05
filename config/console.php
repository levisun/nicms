<?php

/**
 *
 * 控制台配置
 *
 * @package   NiPHP
 * @category  config
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    // 执行用户（Windows下无效）
    'user'     => null,
    // 指令定义
    'commands' => [
        'backup'  => \app\command\Backup ::class,
        'install' => \app\command\Install::class,
    ],
];
