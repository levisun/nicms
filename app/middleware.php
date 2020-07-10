<?php

/**
 *
 * 中间件定义文件
 *
 * @package   NICMS
 * @category  app
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    // 全局请求缓存
    \app\common\middleware\RequestCache::class,
    // 请求合法校验
    \app\common\middleware\RequestCheck::class,
    // 访问频率限制
    \app\common\middleware\Throttle::class,
    // 插件
    \app\common\middleware\Hook::class,
];
