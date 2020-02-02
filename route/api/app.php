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

use think\facade\Config;
use think\facade\Route;
use think\Response;

Route::group(function () {
    // 下载接口
    Route::get('download$', 'Download/index');

    // 操作接口
    Route::post('handle$', 'Handle/index');

    // IP信息接口
    Route::get('ip$', 'Ip/index');

    // 支付
    Route::post('pay/order/:method$', 'Pay/index');
    Route::get('pay/respond/:method$', 'Pay/respond');
    Route::get('pay/notify/:method$', 'Pay/notify');

    // 请求接口
    Route::get('query$', 'Query/index');

    // 访问日志
    Route::get('record$', 'Record/index');

    // 上传文件接口
    Route::post('upload$', 'Upload/index');

    // 验证码接口
    Route::get('verify/img$', 'Verify/img');
    Route::post('verify/img_check$', 'Verify/imgCheck');
    Route::post('verify/sms$', 'Verify/sms');
    Route::post('verify/sms_check$', 'Verify/smsCheck');

    // 微信接口
    Route::get('wechat$', 'Wechat/index');

    Route::miss(function () {
        return Response::create(Config::get('app.app_host'), 'redirect', 302);
    });
})
->domain('api')
->ext('do')
->pattern([
    'method' => '[a-z]+',
]);
