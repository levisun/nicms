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

use think\facade\Env;
use think\facade\Route;

// $cache = boolval(Env::get('app_debug', false)) ? false : mt_rand(1440, 2880);
define('MISS_HTML', '<style type="text/css">*{padding:0;margin:0;}body{background:#fff;font-family:"Century Gothic","Microsoft yahei";color:#333;font-size:18px;}section{text-align:center;margin-top:50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>404</title><section><h2>404</h2><h3>Oops! Page not found.</h3></section>');


Route::domain('book', function () {
    Route::get('/', 'book/index')->ext('html');
    Route::get('index', 'book/index')->ext('html');
    Route::get('list', 'book/catalog')->ext('html');
    Route::get('details', 'book/details')->ext('html');
});


Route::domain(['www', 'm'], function () {
    Route::get('404', 'cms/miss')->append(['code' => '404']);
    Route::get('500', 'cms/miss')->append(['code' => '500']);
    Route::get('502', 'cms/miss')->append(['code' => '502']);
    Route::get('/', 'cms/index')->ext('html');
    Route::get('index', 'cms/index')->ext('html');
    Route::get('list/:name/:cid$', 'cms/lists')->ext('html');
    Route::get('details/:name/:cid/:id$', 'cms/details')->ext('html');
    Route::get('search', 'cms/search')->ext('html');
    Route::miss(function () {
        event('app\event\RecordRequest');
        return MISS_HTML;
    });
})->bind('cms')->pattern([
    'name' => '[a-z]+',
    'cid'  => '\d+',
    'id'   => '\d+',
]);



/**
 * admin后台
 */
Route::domain(Env::get('admin.entry'), function () {
    Route::miss('admin/miss');
    Route::get('404', 'admin/miss')->append(['code' => '404']);
    Route::get('500', 'admin/miss')->append(['code' => '500']);
    Route::get('502', 'admin/miss')->append(['code' => '502']);
    Route::get('/', 'admin/index')->ext('html');
    Route::get(':service/:logic/:action$', 'admin/index')->ext('html');
    Route::get(':service/:logic/:action/:id$', 'admin/index')->ext('html');
})->bind('admin')->pattern([
    'service' => '[a-z]+',
    'logic'   => '[a-z]+',
    'action'  => '[a-z]+',
    'id'      => '\d+',
]);



/**
 * API接口
 * query|ip|download|wechat get请求
 * handle|sms|upload post请求
 */
Route::domain('api', function () {
    Route::get('query$', 'api/query')->ext('do');
    Route::get('ip$', 'api/ip')->ext('do');
    Route::get('download$', 'api/download')->ext('do');
    Route::get('wechat$', 'api/wechat')->ext('do');
    Route::post('handle$', 'api/handle')->ext('do');
    Route::post('upload$', 'api/upload')->ext('do');
    Route::post('sms$', 'api/sms')->ext('do');
    Route::miss(function () {
        event('app\event\RecordRequest');
        return MISS_HTML;
    });
})->bind('api')->middleware('\app\middleware\AllowCrossDomain');



/**
 * CDN
 */
Route::domain('cdn', function () {
    Route::miss(function () {
        event('app\event\RecordRequest');
        return MISS_HTML;
    });
});
