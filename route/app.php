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

// $cache = boolval(Env::get('app_debug', false)) ? false : mt_rand(1440, 2880);

// Route::domain('book', function () {
//     Route::get('/', 'book/index')->ext('html');
//     Route::get('index', 'book/index')->ext('html');
//     Route::get('list', 'book/catalog')->ext('html');
//     Route::get('details', 'book/details')->ext('html');
// });


Route::domain(['www', 'm'], function () {
    Route::get('/', 'cms/index')->ext('html');
    Route::get('index', 'cms/index')->ext('html');
    Route::get('list/:name/:cid$', 'cms/lists')->ext('html');
    Route::get('details/:name/:cid/:id$', 'cms/details')->ext('html');
    Route::get('search', 'cms/search')->ext('html');
    Route::get('404', 'cms/miss')->append(['code' => '404'])->ext('html');
    Route::get('500', 'cms/miss')->append(['code' => '500'])->ext('html');
    Route::get('502', 'cms/miss')->append(['code' => '502'])->ext('html');
    Route::miss(function () {
        event('app\event\RecordRequest');
        return '<style type="text/css">*{padding:0;margin:0;}body{background:#fff;font-family:"Century Gothic","Microsoft yahei";color:#333;font-size:18px;}section{text-align:center;margin-top:50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>404</title><section><h2>404</h2><h3>Oops! Page not found.</h3></section>';
    });
})->bind('cms')->pattern([
    'name' => '[a-z]+',
    'cid'  => '\d+',
    'id'   => '\d+',
]);



/**
 * admin后台
 */




/**
 * API接口
 * query|ip|download|wechat get请求
 * handle|sms|upload post请求
 */




/**
 * CDN
 */
Route::domain('cdn', function () {
    Route::miss(function () {
        event('app\event\RecordRequest');
        return '<style type="text/css">*{padding:0;margin:0;}body{background:#fff;font-family:"Century Gothic","Microsoft yahei";color:#333;font-size:18px;}section{text-align:center;margin-top:50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>404</title><section><h2>404</h2><h3>Oops! Page not found.</h3></section>';
    });
});
