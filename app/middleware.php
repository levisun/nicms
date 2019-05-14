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
    // 'app\middleware\LockingIllegalRequest',     // 必须在前,否则无法拦截非法请求
    'app\middleware\HealthMonitoring',          // 健康状态监控,清除过期缓存和日志等
    'app\middleware\RequestCache',              // 必须在后,否则会影响其他中间件的执行

    // 'think\middleware\CheckRequestCache',
    'think\middleware\LoadLangPack',
    'think\middleware\SessionInit',
    'think\middleware\TraceDebug',
];
