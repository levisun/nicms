<?php

/**
 *
 * 验证器
 * 设置 - 安全设置
 *
 * @package   NICMS
 * @category  app\admin\validate\settings
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

namespace app\admin\validate\settings;

use think\Validate;

class Safe extends Validate
{
    protected $rule = [
        'app_upload_size'   => ['require', 'number'],
        'app_upload_type'   => ['require', 'max:100'],
        // 'database_hostname' => ['require', 'max:100'],
        // 'database_database' => ['require', 'max:100'],
        // 'database_username' => ['require', 'max:100'],
        // 'database_password' => ['require', 'max:100'],
        // 'database_hostport' => ['require', 'max:100'],
        // 'database_prefix'   => ['require', 'max:100'],
        'cache_expire'      => ['require', 'number'],
        'app_debug'       => ['require', 'number'],
        // 'admin_entry'       => ['require', 'max:100'],
    ];

    protected $message = [
        'app_upload_size.require' => '{%please enter app upload size}',
        'app_upload_size.max'     => '{%app upload size length shall not exceed 10}',
        'app_upload_type.require' => '{%please enter app upload type}',
        'app_upload_type.max'     => '{%app upload type length shall not exceed 100}',

        // 'database_hostname.require' => '{%please enter database hostname}',
        // 'database_hostname.max'     => '{%database hostname length shall not exceed 100}',
        // 'database_database.require' => '{%please enter database database}',
        // 'database_database.max'     => '{%database database length shall not exceed 100}',
        // 'database_username.require' => '{%please enter database username}',
        // 'database_username.max'     => '{%database username length shall not exceed 100}',
        // 'database_password.require' => '{%please enter database password}',
        // 'database_password.max'     => '{%database password length shall not exceed 100}',
        // 'database_hostport.require' => '{%please enter database hostport}',
        // 'database_hostport.max'     => '{%database hostport length shall not exceed 100}',
        // 'database_prefix.require' => '{%please enter database prefix}',
        // 'database_prefix.max'     => '{%database prefix length shall not exceed 100}',

        'cache_expire.require' => '{%please enter cache expire}',
        'cache_expire.max'     => '{%cache expire length shall not exceed 100}',
        'app_debug.require' => '{%please enter admin debug}',
        'app_debug.max'     => '{%admin debug length shall not exceed 100}',
        // 'admin_entry.require' => '{%please enter admin entry}',
        // 'admin_entry.max'     => '{%admin entry length shall not exceed 100}',
    ];
}
