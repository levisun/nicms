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
    // Session初始化
    \think\middleware\SessionInit::class,
    // 全局请求缓存
    \app\common\middleware\CheckRequestCache::class,
    // 页面Trace调试
    \think\middleware\TraceDebug::class,
    // 多语言加载
    \think\middleware\LoadLangPack::class,
];
