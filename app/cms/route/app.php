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

/**
 * CDN IMG
 */
Route::domain(['cdn', 'img'], function () {
    Route::miss(function () {
        return miss(404, false);
    });
});

Route::miss(function () {
    return miss(404, false);
});

Route::group(function () {
    // 首页
    Route::get('/$', 'index');
    Route::get('index$', function () {
        return redirect('/', 301);
    });

    // 列表页
    Route::get('list/:cid/[:page]$', 'category');
    Route::get('tags/:id/[:page]$', 'tags');
    Route::get('link/:cid$', 'link');
    Route::get('feedback/:cid$', 'feedback');
    Route::get('message/:cid$', 'message');
    Route::get('search$', 'search');

    // 详情页
    Route::get('details/:cid/:id$', 'details');
    // 单页
    Route::get('page/:cid$', 'details');

    // 跳转接口
    Route::get('go$', 'go');
})->prefix('Index/')->ext('html')->pattern([
    'page' => '\d+',
    'cid'  => '\w+',
    'id'   => '\w+',
]);
