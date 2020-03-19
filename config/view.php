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

use think\facade\Request;

return [
    // 模板引擎类型使用Think
    'type' => \app\common\library\Template::class,

    'compile_path' => '',
];
