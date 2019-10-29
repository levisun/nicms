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
    // 访问日志
    Route::get('accesslog$', 'AccessLog/index')->ext('do');

    // 下载接口
    Route::get('download$', 'Download/index')->ext('do');

    // 操作接口
    Route::post('handle$', 'Handle/index')->ext('do');

    // IP信息接口
    Route::get('ip$', 'Ip/index')->ext('do');

    // 请求接口
    Route::get('query$', 'Query/index')->ext('do');

    // 短信验证码接口
    Route::post('sms$', 'Sms/index')->ext('do');
    Route::post('sms/check$', 'Sms/check')->ext('do');

    // 上传文件接口
    Route::post('upload$', 'Upload/index')->ext('do');

    // 图片验证码接口
    Route::get('verify$', 'Verify/index')->ext('png');

    // 微信接口
    Route::get('wechat$', 'Wechat/index')->ext('do');

    Route::miss(function () {
        return '<style type="text/css">*{padding:0;margin:0;}body{background:#fff;font-family:"Century Gothic","Microsoft yahei";color:#333;font-size:18px;}section{text-align:center;margin-top:50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>404</title><section><h2>404</h2><h3>Oops! Page not found.</h3></section>';
    });
});
