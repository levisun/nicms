<?php

/**
 *
 * CMS路由
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
    Route::get('list/:name/:cid$', 'Category/index')->ext('html');

    // 详情页
    Route::get('details/:name/:cid/:id$', 'Details/index')->ext('html');

    // 搜索页
    Route::get('search', 'Search/index')->ext('html');


    Route::get('404', 'cms/miss')->append(['code' => '404'])->ext('html');
    Route::get('500', 'cms/miss')->append(['code' => '500'])->ext('html');
    Route::get('502', 'cms/miss')->append(['code' => '502'])->ext('html');
    Route::miss(function () {
        // event('app\event\RecordRequest');
        return miss(404);
    });
})->pattern([
    'name' => '[a-z]+',
    'cid'  => '\d+',
    'id'   => '\d+',
]);
