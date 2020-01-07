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
    // 首页
    Route::get('/', 'Index/index')->ext('html');
    Route::get('index', 'Index/index')->ext('html');

    // 列表页
    Route::get('list/:tid$', 'Index/category')->ext('html');
    Route::get('catalog/:id$', 'Index/catalog')->ext('html');

    // 详情页
    Route::get('article/:bid/:id$', 'Index/article')->ext('html');

    // 搜索页
    Route::get('search', 'Index/search')->ext('html');

    Route::miss(function () {
        return miss(404);
    });
})
->domain('book')
->pattern([
    'tid'  => '\d+',
    'bid'  => '\d+',
    'id'   => '\d+',
]);
