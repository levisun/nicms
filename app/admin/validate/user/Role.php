<?php

/**
 *
 * 验证器
 * 用户 - 管理员组
 *
 * @package   NICMS
 * @category  app\admin\validate\user
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

namespace app\admin\validate\user;

use think\Validate;

class Role extends Validate
{
    protected $rule = [
        'name'       => ['require', 'length: 2,20', 'unique: role'],
        'remark'     => ['max: 100'],
        'status'     => ['require', 'integer'],
    ];

    protected $message = [
        'name.require'       => '{%error role name require}',
        'name.length'        => '{%error role name length not}',
        'name.unique'        => '{%error role name unique}',

        'remark.max'         => '{%error remark}',

        'status.require'     => '{%error status}',
        'status.number'      => '{%error status}',
    ];
}
