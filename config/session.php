<?php

/**
 *
 * 会话设置
 *
 * @package   NiPHP
 * @category  config
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    // session name
    'name'           => 'SID',
    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => '',
    // 驱动方式 支持file cache
    'type'           => 'file',
    'path'           => runtime_path('session'),
    // 存储连接标识 当type使用cache的时候有效
    'store'          => null,
    // 过期时间
    'expire'         => 2880,
    // 前缀
    'prefix'         => '',
    // 数据压缩
    'data_compress'  => true,
];
