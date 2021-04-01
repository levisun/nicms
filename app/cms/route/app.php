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
    return miss(404, false);
});

Route::group(function () {
    // 首页
    Route::get('/$', 'index');
    Route::get('index$', function () {
        return redirect('/', 301);
    });

    // 列表页
    Route::get('list/:category_id/[:page]$', 'category');
    Route::get('tags/:id/[:page]$', 'tags');
    Route::get('link/:category_id$', 'link');
    Route::get('feedback/:category_id$', 'feedback');
    Route::get('message/:category_id$', 'message');
    Route::get('search$', 'search');

    // 详情页
    Route::get('details/:category_id/:id$', 'details');
    // 单页
    Route::get('page/:category_id$', 'details');

    // 跳转接口
    Route::get('go$', 'go');
})->prefix('Index/')->ext('html')->pattern([
    'page'        => '\d+',
    'category_id' => '\w+',
    'id'          => '\w+',
]);

/**
 * STATIC IMG
 */
Route::domain(['cdn', 'img'], function () {
    Route::get('static/:app_name$', '\app\api\controller\Secret::index')->ext('do');

    Route::miss(function () {
        return miss(404, false);
    });
});
