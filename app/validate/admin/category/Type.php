<?php
/**
 *
 * 验证器
 * 栏目 - 分类
 *
 * @package   NICMS
 * @category  app\validate\admin\category
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
namespace app\validate\admin\category;

use think\Validate;

class Type extends Validate
{
    protected $rule = [
        'name'        => ['require', 'length:2,20', 'unique:type'],
        'remark'      => ['max: 300'],
        'category_id' => ['require', 'number'],
    ];

    protected $message = [
        'name.require'        => '{%error type name require}',
        'name.length'         => '{%error type name length not}',
        'name.unique'         => '{%error type name unique}',
        'remark.max'          => '{%error remark}',
        'category_id.require' => '{%error category_id}',
        'category_id.number'  => '{%error category_id}',
    ];
}
