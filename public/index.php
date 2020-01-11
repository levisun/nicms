<?php

/**
 *
 * 应用入口文件
 *
 * @package   NICMS
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 *
 */

namespace think;

require __DIR__ . '/../vendor/autoload.php';

// 执行应用并响应
$http = (new App())->http;

$response = $http->run();

$response->send();

$http->end($response);
