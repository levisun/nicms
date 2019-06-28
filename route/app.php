<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\facade\Env;
use think\facade\Route;

Route::miss('error/index');
Route::rule('error', 'error/index');
Route::rule('404', 'error/_404');
Route::rule('500', 'error/_500');

Route::domain(['www', 'm'], function () {
    Route::get('/', 'cms/index');
    Route::get('index', 'cms/index');
    Route::get('list/:name/:cid$', 'cms/lists');
    Route::get('details/:name/:cid/:id$', 'cms/details');
    Route::get('search', 'cms/search');
})->bind('cms')->ext('html')->cache(1440);

Route::domain(['cdn'], function () {
    $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>404</title><section><h2>404</h2><h3>Oops! Page not found.</h3></section>';
    http_response_code(404);
    echo $error;
    exit();
});

Route::domain(Env::get('admin.entry'), function () {
    Route::get('/', 'admin/index');
    Route::get(':logic/:controller/:action$', 'admin/index');
    Route::get(':logic/:controller/:action/:id$', 'admin/index');
})->bind('admin')->ext('html')->cache(1440);

Route::domain('api', function () {
    Route::rule('download$', 'api/download');
    Route::rule('ip$', 'api/ip');
    Route::rule('query$', 'api/query');
    Route::rule('handle$', 'api/handle');
    Route::rule('upload$', 'api/upload');
})->bind('api')->ext('do')->middleware('app\middleware\AllowCrossDomain');
