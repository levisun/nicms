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
    \app\api\middleware\AllowCrossDomain::class,
    // 全局请求缓存
    \app\common\middleware\CheckRequestCache::class,
];
