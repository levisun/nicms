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

/**
 * CDN
 */
Route::domain('cdn', function () {
    Route::miss(function () {
        return app('request')->url();
        return miss(404);
    });
});

Route::group(function () {
    // 首页
    Route::get('/', 'Index/index')->ext('html');
    Route::get('index', 'Index/index')->ext('html');

    // 列表页
    Route::get('list/:name/:cid$', 'Category/index')->ext('html');

    // 详情页
    Route::get('article/:name/:cid/:id$', 'Article/index')->ext('html');

    // 搜索页
    Route::get('search', 'Search/index')->ext('html');

    Route::miss('Index/miss');
})->pattern([
    'name' => '[a-z]+',
    'cid'  => '\d+',
    'id'   => '\d+',
]);
