<?php

/**
 *
 * 验证器
 * 用户 - 会员
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

class User extends Validate
{
    protected $rule = [
        'username' => ['require', 'length: 4,20', 'unique: user,username'],
        'password' => ['require', 'length: 6,20', 'confirm'],
        'phone'    => ['mobile', 'unique: user,phone'],
        'email'    => ['email', 'unique: user,email'],
        'level_id' => ['require', 'integer', 'gt: 0'],
        'status'   => ['require', 'integer'],
    ];

    protected $message = [
        'username.require' => '{%error username require}',
        'username.length'  => '{%error username length not}',
        'username.unique'  => '{%error username unique}',

        'password.require' => '{%error password require}',
        'password.length'  => '{%error password length not}',
        'password.confirm'  => '{%error password confirm}',

        'phone.mobile'  => '{%error phone mobile}',
        'phone.unique'  => '{%error phone unique}',

        'email.email'  => '{%error email email}',
        'email.unique'  => '{%error email unique}',
    ];
}
