<?php

/**
 *
 * Cookie设置
 *
 * @package   NiPHP
 * @category  config
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    // cookie 保存时间
    'expire'    => 0,
    // cookie 保存路径
    'path'      => '/',
    // cookie 有效域名
    'domain'    => '.' . app('request')->rootDomain(),
    //  cookie 启用安全传输
    'secure'    => false,
    // httponly设置
    'httponly'  => true,
    // 是否使用 setcookie
    'setcookie' => true,
];
