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
 * CB|Alpha 内测版 RC|Beta  正式候选版 Demo 演示版
 * Stable 稳定版 Release 正式版
 */

namespace think;

// version_compare(PHP_VERSION, '7.3.0', '>=') or die('系统需要PHP7.3+版本! 当前PHP版本:' . PHP_VERSION);

require __DIR__ . '/../vendor/autoload.php';

// 执行应用并响应
$http = (new App())->http;

$response = $http->run();

$response->send();

$http->end($response);
