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

use think\facade\Env;

return [
    // 模板引擎类型使用Think
    'type'         => \app\common\library\Template::class,
    // 模板路径
    'view_path'    => app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR,
    'cache_path'   => app()->getRuntimePath() . 'compile' . DIRECTORY_SEPARATOR,
    'tpl_cache'    => (bool) !Env::get('app_debug', false),
    'strip_space'  => (bool) !Env::get('app_debug', false),
];
