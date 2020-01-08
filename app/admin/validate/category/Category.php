<?php

/**
 *
 * 验证器
 * 栏目 - 栏目
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

class Category extends Validate
{
    protected $rule = [
        'name'        => ['require', 'length:2,20'],
        'aliases'     => ['max:20', 'alpha'],
        'title'       => ['max:50'],
        'keywords'    => ['max:100'],
        'description' => ['max:300'],
        'image'       => ['max:100'],
        'type_id'     => ['require', 'integer'],
        'model_id'    => ['require', 'integer'],
        'is_show'     => ['require', 'integer'],
        'is_channel'  => ['require', 'integer'],
        'sort_order'  => ['require', 'integer'],
        'access_id'   => ['require', 'integer'],
        'url'         => ['max:100'],
    ];

    protected $message = [
        'name.require'       => '{%error category name require}',
        'name.length'        => '{%error category name length not}',
        'name.unique'        => '{%error category name unique}',
        'aliases.length'     => '{%error aliases length not}',
        'aliases.unique'     => '{%error aliases unique}',
        'aliases.alpha'      => '{%error aliases alpha}',
        'title.max'          => '{%error title}',
        'keywords.max'       => '{%error keywords}',
        'description.max'    => '{%error description}',
        'image.max'          => '{%error image}',
        'type_id.require'    => '{%error type}',
        'type_id.integer'    => '{%error type}',
        'model_id.require'   => '{%error model1}',
        'model_id.integer'   => '{%error model2}',
        'is_show.require'    => '{%error is_show}',
        'is_show.integer'    => '{%error is_show}',
        'is_channel.require' => '{%error is_channel}',
        'is_channel.integer' => '{%error is_channel}',
        'sort_order.require' => '{%error sort_order}',
        'sort_order.integer' => '{%error sort_order}',
        'access_id.require'  => '{%error access}',
        'access_id.integer'  => '{%error access}',
        'url.max'            => '{%error url}',
    ];
}
