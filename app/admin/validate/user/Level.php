<?php

/**
 *
 * 验证器
 * 用户 - 会员等级
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

class Level extends Validate
{
    protected $rule = [
        'name'   => ['require', 'length: 4,20', 'unique: level,name'],
        'credit' => ['require', 'integer'],
        'status' => ['require', 'integer'],
    ];

    protected $message = [
        'name.require' => '{%error name require}',
        'name.length'  => '{%error name length not}',
        'name.unique'  => '{%error name unique}',

        'credit.require'  => '{%error credit require}',
        'credit.length'   => '{%error credit length}',

        'status.require'  => '{%error status require}',
        'status.length'   => '{%error status length}',
    ];
}
