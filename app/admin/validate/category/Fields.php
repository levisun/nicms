<?php

/**
 *
 * 验证器
 * 栏目 - 自定义字段
 *
 * @package   NICMS
 * @category  app\admin\validate\category
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

namespace app\admin\validate\category;

use think\Validate;

class Fields extends Validate
{
    protected $rule = [
        'category_id' => ['require', 'integer'],
        'type_id'     => ['require', 'integer'],
        'name'        => ['require', 'length: 2,20'],
        'maxlength'   => ['require', 'integer'],
        'is_require'  => ['require', 'integer'],
        'sort_order'  => ['require', 'integer'],
        'remark'      => ['max: 300'],
    ];

    protected $message = [
        'category_id.require' => '{%error category}',
        'category_id.integer' => '{%error category}',
        'type_id.require'     => '{%error type}',
        'type_id.integer'     => '{%error type}',
        'name.require'        => '{%error category name require}',
        'name.length'         => '{%error category name length not}',
        'maxlength.require'   => '{%error maxlength}',
        'maxlength.integer'   => '{%error maxlength}',
        'is_require.require'  => '{%error is_require}',
        'is_require.integer'  => '{%error is_require}',
        'sort_order.require'  => '{%error sort_order}',
        'sort_order.integer'  => '{%error sort_order}',
        'remark.max'          => '{%error remark}',
    ];
}
