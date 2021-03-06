<?php

/**
 *
 * 验证器
 * 用户 - 节点
 *
 * @package   NICMS
 * @category  app\admin\validate\user
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

namespace app\admin\validate\user;

use think\Validate;

class Node extends Validate
{
    protected $rule = [
        'name'       => ['require', 'length: 2,20', /* 'unique: node' */],
        'title'      => ['require', 'length: 2,20', /* 'unique: node' */],
        'remark'     => ['max: 100'],
        'pid'        => ['require', 'integer'],
        'level'      => ['require', 'integer', 'gt: 0'],
        'status'     => ['require', 'integer'],
        'sort_order' => ['require', 'integer'],
    ];

    protected $message = [
        'name.require'       => '{%error node name require}',
        'name.length'        => '{%error node name length not}',
        'name.unique'        => '{%error node name unique}',

        'title.require'      => '{%error node title require}',
        'title.length'       => '{%error node title length not}',
        'title.unique'       => '{%error node title unique}',

        'remark.max'         => '{%error remark}',

        'pid.require'        => '{%error pid}',
        'pid.number'         => '{%error pid}',

        'level.require'      => '{%error level}',
        'level.number'       => '{%error level}',

        'status.require'     => '{%error status}',
        'status.number'      => '{%error status}',

        'sort_order.require' => '{%error sort_order}',
        'sort_order.number'  => '{%error sort_order}',
    ];
}
