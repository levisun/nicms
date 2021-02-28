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

Route::domain('book', function () {
    // 首页
    Route::get('/$', 'Index/index');
    Route::get('index$', function () {
        return redirect('/', 301);
    });


    // 列表页
    Route::get('category/:book_type_id$', 'Index/category')->ext('html')->pattern([
        'book_type_id' => '\w+',
    ]);
    Route::get('book/:book_id$', 'Index/book')->ext('html')->pattern([
        'book_id' => '\w+',
    ]);

    // 详情页
    Route::get('article/:book_id/:id$', 'Index/article')->ext('html')->pattern([
        'book_id' => '\w+',
        'id'      => '\w+',
    ]);

    // 搜索页
    Route::get('search$', 'Index/search');
});
