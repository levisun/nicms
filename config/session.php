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
    'name'           => '__' . substr(sha1(__DIR__), 7, 3) . 'i',
    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => '',
    // 驱动方式 支持file redis memcache memcached
    'type'           => 'app\library\Session',
    // 过期时间
    'expire'         => 0,
];
