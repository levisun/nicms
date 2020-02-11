<?php

/**
 *
 * 验证器
 * 内容 - 友情链接
 *
 * @package   NICMS
 * @category  app\admin\validate\content
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

namespace app\admin\validate\content;

use think\Validate;

class Link extends Validate
{
    protected $rule = [
        'title'       => ['require', 'length: 2,50'],
        'url'         => ['require', 'url'],
        'description' => ['max: 300'],
        'category_id' => ['require', 'integer'],
        // 'model_id'    => ['require', 'integer'],
        'type_id'     => ['integer'],
        'is_pass'     => ['integer'],
        'sort_order'  => ['integer'],
        'admin_id'    => ['integer'],
    ];

    protected $message = [
        'title.require'       => '{%error title require}',
        'title.length'        => '{%error title length not}',
        'url.require'         => '{%error url require}',
        'url.url'             => '{%error url not}',
        'description.max'     => '{%error description}',
        'category_id.require' => '{%error category_id}',
        'category_id.integer' => '{%error category_id}',
        // 'model_id.require'    => '{%error model_id}',
        // 'model_id.integer'    => '{%error model_id}',
        'type_id.integer'     => '{%error type_id}',
        'is_pass.integer'     => '{%error is_pass}',
        'sort_order.integer'  => '{%error sort_order}',
        'admin_id.integer'    => '{%error admin_id}',
    ];
}
