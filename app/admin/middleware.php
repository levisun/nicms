<?php

/**
 *
 * 中间件定义文件
 *
 * @package   NICMS
 * @category  app\admin
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    // 全局请求缓存
    \app\common\middleware\RequestCache::class,
    // Session初始化
    \think\middleware\SessionInit::class,
    // 多语言加载
    \think\middleware\LoadLangPack::class,
    // 访问限制
    \app\common\middleware\Throttle::class,
];
