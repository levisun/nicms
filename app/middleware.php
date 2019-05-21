<?php
/**
 *
 * 中间件定义文件
 * 请勿修改顺序
 *
 * @package   NICMS
 * @category  app
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    // 请求缓存
    'app\middleware\RequestCache',

    // 全局请求缓存
    // 'think\middleware\CheckRequestCache',
    // 多语言加载
    'think\middleware\LoadLangPack',
    // Session初始化
    // 'think\middleware\SessionInit',
    // 页面Trace调试
    // 'think\middleware\TraceDebug',
];
