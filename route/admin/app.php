<?php

/**
 *
 * ADMIN路由
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


    Route::get('404', 'Index/miss')->append(['code' => '404'])->ext('html');
    Route::get('500', 'Index/miss')->append(['code' => '500'])->ext('html');
    Route::get('502', 'Index/miss')->append(['code' => '502'])->ext('html');
    Route::miss(function () {
        return file_get_contents(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . '404.html');
    });
})->pattern([
    'logic'  => '[a-z]+',
    'action' => '[a-z]+',
    'method' => '[a-z]+',
    'id'     => '\d+',
]);
