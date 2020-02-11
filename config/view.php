<?php

/**
 *
 * 模板设置
 *
 * @package   NiPHP
 * @category  config
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

use app\common\library\Base64;

return [
    // 模板引擎类型使用Think
    'type' => \app\common\library\Template::class,

    'compile_path' => app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'compile' . DIRECTORY_SEPARATOR .
        Base64::flag(__DIR__ . __LINE__) . DIRECTORY_SEPARATOR,
];
