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

Route::group(function () {
    Route::get('/', 'Index/index')->ext('html');
    Route::get(':logic/:action/:method$', 'Index/index')->ext('html');
    Route::get(':logic/:action/:method/:id$', 'Index/index')->ext('html');

    Route::miss(function () {
        return miss(404);
    });
})
->domain('my')
->pattern([
    'logic'  => '[a-z]+',
    'action' => '[a-z]+',
    'method' => '[a-z]+',
    'id'     => '\d+',
]);
