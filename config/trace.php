<?php

/**
 *
 * Trace设置 开启 app_trace 后 有效
 *
 * @package   NiPHP
 * @category  config
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    // 内置Html Console 支持扩展
    'type' => 'Console',
    'trace_tabs' =>  [
        'base'  => '基本',
        'file'  => '文件',
        // 'info'  => '流程',
        'error|notice|warning' => '错误',
        // 'sql'   => 'SQL',
        // 'debug' => '调试',
        // 'user'  => '用户'
    ]
];
