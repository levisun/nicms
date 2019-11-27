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
    // USER_AGENT生成
    'name'           => strtoupper(
        substr(
            sha1(__DIR__ . app('request')->rootDomain() . app('request')->server('HTTP_USER_AGENT')),
            7,
            7
        )
    ),
    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => '',
    // 驱动方式 支持file cache
    'type'           => 'file',
    // 存储连接标识 当type使用cache的时候有效
    'store'          => null,
    // 过期时间
    'expire'         => 28800,
    // 前缀
    'prefix'         => '',
    // 数据压缩
    'data_compress'  => true,
];
