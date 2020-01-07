<?php

/**
 *
 * 容器Provider定义文件
 *
 * @package   NICMS
 * @category  app
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

use app\ExceptionHandle;
use app\Request;

return [
    'think\Request'          => Request::class,
    'think\exception\Handle' => ExceptionHandle::class,
];
