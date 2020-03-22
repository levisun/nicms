<?php
/**
 *
 * 验证层
 * 登录
 *
 * @package   NICMS
 * @category  app\admin\validate\account
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
namespace app\admin\validate\account;

use think\Validate;

class User extends Validate
{
    protected $rule = [
        'captcha'  => ['require', 'captcha'],
        'username' => ['require', 'length:4,20'],
        'password' => ['require', 'max:20'],
    ];

    protected $message = [
        'captcha.require'  => '{%error captcha require}',
        'captcha.captcha'  => '{%error captcha}',

        'username.require' => '{%error username require}',
        'username.length'  => '{%error username length not}',
        'password.require' => '{%error password require}',
        'password.length'  => '{%error password length not}',
        'captcha.require'  => '{%error captcha require}',
    ];
}
