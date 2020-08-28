<?php

/**
 *
 * 中间件定义文件
 *
 * @package   NICMS
 * @category  app
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    // 访问限制
    \app\common\middleware\Throttle::class,
    // 插件
    \app\common\middleware\Hook::class,
];
