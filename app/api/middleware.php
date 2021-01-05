<?php

/**
 *
 * 中间件定义文件
 *
 * @package   NICMS
 * @category  app\api
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    // 跨域
    \app\common\middleware\AllowCrossDomain::class,
    // 请求缓存
    // \app\common\middleware\ApiRequestCache::class,
];
