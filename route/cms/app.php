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
        return '<style type="text/css">*{padding:0;margin:0;}body{background:#fff;font-family:"Century Gothic","Microsoft yahei";color:#333;font-size:18px;}section{text-align:center;margin-top:50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>404</title><section><h2>404</h2><h3>Oops! Page not found.</h3></section>';
    });
})->pattern([
    'name' => '[a-z]+',
    'cid'  => '\d+',
    'id'   => '\d+',
]);
