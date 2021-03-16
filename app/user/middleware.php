<?php

/**
 *
 * 中间件定义文件
 *
 * @package   NICMS
 * @category  app\cms
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    // Session初始化
    \think\middleware\SessionInit::class,
    // 多语言加载
    \think\middleware\LoadLangPack::class,
    // 全局请求缓存
    // \think\middleware\CheckRequestCache::class,
    // 插件
    \app\common\middleware\Hook::class,
];
