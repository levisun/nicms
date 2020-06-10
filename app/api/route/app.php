<?php

/**
 *
 * 路由
 *
 * @package   NICMS
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

use think\facade\Route;

Route::group(function () {
    // 工具接口
    Route::get('download$', 'tools.Download/index');    // 下载接口
    Route::get('ip$', 'tools.Ip/index');                // IP信息接口
    Route::get('record$', 'tools.Record/index');        // 访问日志
    Route::get('spider$', 'tools.Spider/index');        // 爬虫
    Route::get('words$', 'tools.Words/index');        // 爬虫

    // 验证码接口
    Route::get('verify/img$', 'verify.Img/index');
    Route::post('verify/sms$', 'verify.Sms/index');
    Route::post('verify/sms_check$', 'verify.Sms/check');

    // 支付
    Route::post('pay/order/:method$', 'pay.Order/index');
    Route::get('pay/respond/:method$', 'pay.Respond/index');
    Route::get('pay/notify/:method$', 'pay.Notify/index');

    // 操作接口
    Route::post('handle$', 'Handle/index');
    // 请求接口
    Route::get('query$', 'Query/index');
    // 上传接口
    Route::post('upload$', 'Upload/index');

    Route::miss(function () {
        return miss(404, false);
    });
})
->domain('api')
->ext('do')
->pattern([
    'method' => '[a-z]+',
]);
