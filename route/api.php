<?php

/**
 *
 * API路由
 *
 * @package   NICMS
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

use think\facade\Route;

Route::domain('api', function () {
    Route::rule('query$', '\app\api\controller\Query@index')->ext('do');
    Route::rule('handle$', '\app\api\controller\Handle@index')->ext('do');
    Route::rule('upload$', '\app\api\controller\Upload@index')->ext('do');
    Route::rule('sms$', '\app\api\controller\Sms@index')->ext('do');
    Route::rule('ip$', '\app\api\controller\Ip@index')->ext('do');
    Route::rule('download$', '\app\api\controller\Download@index')->ext('do');
    Route::rule('wechat$', '\app\api\controller\Wechat@index')->ext('do');
    Route::rule('verify$', function () {
        return \think\captcha\facade\Captcha::create();
    })->ext('do');
    Route::miss(function () {
        // event('app\event\RecordRequest');
        return '<style type="text/css">*{padding:0;margin:0;}body{background:#fff;font-family:"Century Gothic","Microsoft yahei";color:#333;font-size:18px;}section{text-align:center;margin-top:50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>404</title><section><h2>404</h2><h3>Oops! Page not found.</h3></section>';
    });
})->middleware('\app\api\middleware\AllowCrossDomain');
