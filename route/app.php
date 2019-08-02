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

Route::domain('cdn', function () {
    // http_response_code(404);
    // echo '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>404</title><section><h2>404</h2><h3>Oops! Page not found.</h3></section>';
    // exit();
});

Route::domain(['www', 'm'], function () {
    Route::miss('cms/_404');
    Route::get('/', 'cms/index')->ext('html');
    Route::get('index', 'cms/index')->ext('html');
    Route::get('list/:name/:cid$', 'cms/lists')->ext('html');
    Route::get('details/:name/:cid/:id$', 'cms/details')->ext('html');
    Route::get('search', 'cms/search')->ext('html');
})->bind('cms')->pattern([
    'name' => '[a-z]+',
    'cid'  => '\d+',
    'id'   => '\d+',
]);

Route::domain(Env::get('admin.entry'), function () {
    Route::miss('admin/_404');
    Route::get('/', 'admin/index')->ext('html');
    Route::get(':service/:logic/:action$', 'admin/index')->ext('html');
    Route::get(':service/:logic/:action/:id$', 'admin/index')->ext('html');
})->bind('admin')->pattern([
    'service' => '[a-z]+',
    'logic'   => '[a-z]+',
    'action'  => '[a-z]+',
    'id'      => '\d+',
]);

Route::domain('api', function () {
    Route::rule('query$', 'api/query')->ext('do');
    Route::rule('handle$', 'api/handle')->ext('do');
    Route::rule('upload$', 'api/upload')->ext('do');
    Route::rule('sms$', 'api/sms')->ext('do');
    Route::rule('ip$', 'api/ip')->ext('do');
    Route::rule('download$', 'api/download')->ext('do');
    Route::rule('wechat$', 'api/wechat')->ext('do');
    Route::miss(function () {
        $params = array_merge($_POST, $_FILES);
        $params = !empty($params) ? "\r" . json_encode($params) : '';
        app('log')->record(app('request')->url(true) . $params, 'info');

        return '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>404</title><section><h2>404</h2><h3>Oops! Page not found.</h3></section>';
    });
})->bind('api')->middleware('app\middleware\AllowCrossDomain');
