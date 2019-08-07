<?php

/**
 *
 * 验证器
 * 栏目 - 栏目
 *
 * @package   NICMS
 * @category  app\validate\admin\node
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

namespace app\validate\admin\user;

use think\Validate;

class Node extends Validate
{
    protected $rule = [
        'name'       => ['require', 'length: 2,20', /* 'unique: node' */],
        'title'      => ['require', 'length: 2,20', /* 'unique: node' */],
        'remark'     => ['max: 100'],
        'pid'        => ['require', 'number'],
        'level'      => ['require', 'number'],
        'status'     => ['require', 'number'],
        'sort_order' => ['require', 'number'],
    ];

    protected $message = [
        'name.require'       => '{%error node name require}',
        'name.length'        => '{%error node name length not}',
        'name.unique'        => '{%error node name unique}',

        'title.require'      => '{%error node title require}',
        'title.length'       => '{%error node title length not}',
        'title.unique'       => '{%error node title unique}',

        'remark.max'         => '{%error remark}',

        'pid.require'        => '{%error type}',
        'pid.number'         => '{%error type}',
        'level.require'      => '{%error level}',
        'level.number'       => '{%error level}',
        'status.require'     => '{%error status}',
        'status.number'      => '{%error status}',
        'sort_order.require' => '{%error sort_order}',
        'sort_order.number'  => '{%error sort_order}',
    ];
}
