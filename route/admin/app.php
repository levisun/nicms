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
use think\Response;

Route::group(function () {
    Route::get('/$', function(){
        return Response::create(url('account/user/login'), 'redirect', 302);
    });
    Route::get('index$', function(){
        return Response::create(url('account/user/login'), 'redirect', 302);
    });

    Route::get(':logic/:action/:method/[:id]$', 'Index/index');

    Route::miss(function () {
        return miss(404);
    });
})
->domain(Env::get('admin.entry', 'admin'))
->ext('html')
->pattern([
    'logic'  => '[a-z]+',
    'action' => '[a-z]+',
    'method' => '[a-z]+',
    'id'     => '\d+',
]);
