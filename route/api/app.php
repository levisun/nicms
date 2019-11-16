<?php

/**
 *
 * API路由
 *
 * @package   NICMS
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

use think\facade\Route;

Route::group(function () {
    // 下载接口
    Route::get('download$', 'Download/index')->ext('do');

    // 操作接口
    Route::post('handle$', 'Handle/index')->ext('do');

    // IP信息接口
    Route::get('ip$', 'Ip/index')->ext('do');

    // 支付
    Route::get('pay/:type$', 'Pay/index')->ext('do');
    Route::get('pay/respond/:type$', 'Pay/respond')->ext('do');
    Route::get('pay/notify/:type$', 'Pay/notify')->ext('do');

    // 请求接口
    Route::get('query$', 'Query/index')->ext('do');

    // 访问日志
    Route::get('record$', 'Record/index')->ext('do');

    // 上传文件接口
    Route::post('upload$', 'Upload/index')->ext('do');

    // 验证码接口
    Route::post('verify/:type$', 'Verify/index')->ext('do');
    Route::post('verify/check/:type$', 'Verify/check')->ext('do');

    // 微信接口
    Route::get('wechat$', 'Wechat/index')->ext('do');

    Route::miss(function () {
        return file_get_contents(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . '404.html');
    });
});
