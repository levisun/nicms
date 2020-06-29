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
    Route::get('/$', 'Index/index');
    Route::get('index$', 'Index/index');


    // 列表页
    Route::get('category/:tid$', 'Index/category');
    Route::get('list/:id$', 'Index/catalog');

    // 详情页
    Route::get('article/:bid/:id$', 'Index/article');

    // 搜索页
    Route::get('search$', 'Index/search');

    Route::miss(function () {
        return miss(404, false);
    });
})
->domain('book')
->ext('html')
->pattern([
    'tid'  => '\d+',
    'bid'  => '\d+',
    'id'   => '\d+',
]);
