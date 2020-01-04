<?php

/**
 *
 * 中间件定义文件
 *
 * @package   NICMS
 * @category  app\api
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    // 跨域
    \app\common\middleware\AllowCrossDomain::class,
    // 请求缓存在\app\common\event\CheckRequest::class中定义
    // API不做文件[redis]缓存
    // 请勿使用\app\common\middleware\CheckRequestCache::class中间件
];
