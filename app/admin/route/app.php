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
    return miss(404);
});

Route::domain(env('admin.entry', 'admin'), function () {
    Route::get('/$', function () {
        return redirect(url('account/user/login'), 301);
    });
    Route::get('index$', function () {
        return redirect('/', 301);
    });

    Route::get(':logic/:action/:method/[:id]$', 'Index/index')->ext('html')->pattern([
        'logic'  => '[a-z]+',
        'action' => '[a-z]+',
        'method' => '[a-z]+',
        'id'     => '\d+',
    ]);
});
