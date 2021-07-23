<?php

/**
 *
 * 路由
 *
 * @package   NICMS
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

use think\facade\Route;

Route::miss(function () {
    return miss(404);
});

Route::domain('api', function () {
    // office接口
    Route::get('office/view$', 'office.View/iframe')->cache(28800);
    Route::post('office/excel/read$', 'office.Excel/read')->cache(28800);
    Route::post('office/excel/write$', 'office.Excel/write');
    Route::post('office/word/write$', 'office.Word/write');

    // 工具接口
    Route::get('tools/download$', 'tools.Download/index')->cache(28800);        // 下载接口
    Route::get('tools/ip$', 'tools.IpV4/index')->cache(28800);                  // IP信息接口
    Route::post('tools/participle$', 'tools.Participle/index')->cache(28800);   // 分词
    Route::post('tools/report$', 'tools.Report/index');                         // 举报
    Route::get('tools/spider$', 'tools.Spider/index')->cache(28800);            // 爬虫

    // 验证码接口
    Route::get('verify/img$', 'verify.Img/index');
    Route::post('verify/sms$', 'verify.Sms/index');
    Route::post('verify/sms_check$', 'verify.Sms/check');

    // 支付
    Route::post('pay/order/:pay/:type$', 'pay.Order/index')->pattern([
        'pay'  => '[a-z]+',
        'type' => '[a-z0-9]+',
    ]);
    Route::get('pay/respond/:pay$', 'pay.Respond/index')->pattern([
        'pay' => '[a-z]+',
    ]);
    Route::get('pay/notify/:pay$', 'pay.Notify/index')->pattern([
        'pay' => '[a-z]+',
    ]);

    Route::post('handle$', 'Handle/index');             // 操作接口
    Route::post('upload$', 'Upload/index');             // 上传接口
    Route::get('query$', 'Query/index');                // 请求接口
    Route::get('ajax$', 'Query/index')->cache(1440);    // 请求接口

})->middleware(\app\common\middleware\AllowCrossDomain::class)->ext('do');

/**
 * STATIC IMG
 */
Route::domain(['cdn', 'img'], function () {
    Route::get('static/app_config$', '\app\api\controller\AppConfig::index')
    ->middleware(\think\middleware\SessionInit::class)
    ->cache(false);

    Route::miss(function () {
        return miss(404);
    });
})->ext('do');
