<?php

/**
 *
 * 会话设置
 *
 * @package   NiPHP
 * @category  config
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    // session name
    'name'           => substr(strtoupper(hash_hmac(
        'sha256',
        request()->rootDomain() . __DIR__ . request()->server('HTTP_USER_AGENT', request()->ip()),
        sha1(__DIR__)
    )), 7, 7),
    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => '',
    // 驱动方式 支持file cache
    'type'           => 'file',
    // 存储连接标识 当type使用cache的时候有效
    'store'          => null,
    // 过期时间
    'expire'         => 1440,
    // 前缀
    'prefix'         => '',
    // 数据压缩
    'data_compress'  => true,
];
