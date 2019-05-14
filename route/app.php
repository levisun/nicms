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

// use think\facade\Config;
use think\facade\Env;
use think\facade\Request;
use think\facade\Route;

Route::miss('error/index');
Route::get('error', 'error/index');
Route::get('404', 'error/_404');
Route::get('500', 'error/_500');

$domain = Request::subDomain();
if ('api' === $domain) {
    Route::get('download$', 'api/download');
    Route::get('ip$', 'api/ip');
    Route::get('query$', 'api/query');
    Route::post('handle$', 'api/handle');
    Route::post('upload$', 'api/upload');

    Route::ext('do')->middleware('app\middleware\AllowCrossDomain');
    // ->pattern([
    //     'appid'     => '\d+',
    //     'timestamp' => '\d+',
    //     'method'    => '\w+',
    //     'sign'      => '\w+',
    // ]);
}
elseif ('www' === $domain) {
    Route::ext('html');
    Route::get('/', 'cms/index');
    Route::get('index', 'cms/index');
    Route::get('list/:name/:cid$', 'cms/lists');
    Route::get('details/:name/:cid/:id$', 'cms/details');
    Route::get('search', 'cms/search');
    // Route::ext('html')->middleware('app\middleware\HealthMonitoring');
}

elseif (Env::get('admin.entry') === $domain) {
    Route::ext('html');
    Route::get('/', 'admin/index');
    Route::get(':logic/:controller/:action$', 'admin/index');
    Route::get(':logic/:controller/:action/:id$', 'admin/index');
}

else {
    die();
    Route::redirect('*', 'error/index');
    $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><section><h2>404</h2><h3>Oops! Page not found.</h3></section>';
    $response = \think\Response::create($error, '', 404);
    throw new \think\exception\HttpResponseException($response);
}




// Route::domain('www', function(){
//     Route::get('/', 'cms/index');
//     Route::get('index', 'cms/index');
//     Route::get('list/:name/:cid$', 'cms/lists');
//     Route::get('details/:name/:cid/:id$', 'cms/details');
//     Route::get('search', 'cms/search');
// })
// ->bind('cms')
// ->cache(Config::get('cache.expire'))
// ->ext('html');


// Route::domain('admin', function(){
//     Route::get('/', 'admin/index');
//     Route::get(':logic/:controller/:action$', 'admin/index');
// })
// ->bind('admin')
// ->ext('html');


// Route::domain('api', function(){
//     Route::get(':name$', 'api/query');
//     Route::post('handle/:name$', 'api/handle');
//     Route::post('upload/:name$', 'api/upload');

//     $headers = [
//         'Access-Control-Allow-Origin'  => Request::server('HTTP_ORIGIN', '*'),
//         // 'Access-Control-Allow-Methods' => 'GET, POST, PATCH, PUT, DELETE',
//         'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
//         'Access-Control-Allow-Headers' => 'Accept, Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With'
//     ];
//     if (Request::isOptions()) {
//         $headers['Access-Control-Max-Age'] = 14400;
//     }

//     Route::allowCrossDomain(true, $headers);
// })
// ->bind('api')
// ->ext('do');
