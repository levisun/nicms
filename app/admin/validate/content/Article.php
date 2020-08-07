<?php

/**
 *
 * 验证器
 * 内容 - 文章
 *
 * @package   NICMS
 * @category  app\admin\validate\content
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

namespace app\admin\validate\content;

use think\Validate;

class Article extends Validate
{
    protected $rule = [
        'title'       => ['require', 'length: 2,50'],
        'keywords'    => ['max: 100'],
        'description' => ['max: 300'],
        'category_id' => ['require', 'integer'],
        'model_id'    => ['require', 'integer'],
        'type_id'     => ['integer'],
        'is_pass'     => ['integer'],
        'is_com'      => ['integer'],
        'is_top'      => ['integer'],
        'is_hot'      => ['integer'],
        'sort_order'  => ['integer'],
        'username'    => ['max: 20'],
        'admin_id'    => ['integer'],
        'user_id'     => ['integer'],
        'access_id'   => ['integer'],
    ];

    protected $message = [
        'title.require'       => '{%error title require}',
        'title.length'        => '{%error title length not}',
        'keywords.max'        => '{%error keywords}',
        'description.max'     => '{%error description}',
        'category_id.require' => '{%error category_id}',
        'category_id.integer' => '{%error category_id}',
        'model_id.require'    => '{%error model_id}',
        'model_id.integer'    => '{%error model_id}',
        'type_id.integer'     => '{%error type_id}',
        'is_pass.integer'     => '{%error is_pass}',
        'is_com.integer'      => '{%error is_com}',
        'is_top.integer'      => '{%error is_top}',
        'is_hot.integer'      => '{%error is_hot}',
        'sort_order.integer'  => '{%error sort_order}',
        'username.max'        => '{%error username}',
        'admin_id.integer'    => '{%error admin_id}',
        'user_id.integer'     => '{%error user_id}',
        'access_id.integer'   => '{%error access_id}',
    ];
}
