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

    // 上传文件接口
    Route::post('upload$', 'Upload/index')->ext('do');

    // 验证码接口
    Route::get('verify/image$', 'Verify/image')->ext('do');
    Route::post('verify/sms$', 'Verify/sms')->ext('do');
    Route::post('verify/sms/check$', 'Verify/smsCheck')->ext('do');

    // 微信接口
    Route::get('wechat$', 'Wechat/index')->ext('do');

    Route::miss(function () {
        return file_get_contents(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . '404.html');
    });
});
