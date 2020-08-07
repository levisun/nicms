<?php

/**
 *
 * 验证器
 * 书籍 - 书籍
 *
 * @package   NICMS
 * @category  app\admin\validate\book
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

namespace app\admin\validate\book;

use think\Validate;

class Book extends Validate
{
    protected $rule = [
        'title'       => ['max:50'],
        'keywords'    => ['max:100'],
        'description' => ['max:300'],
        'image'       => ['max:100'],
        'type_id'     => ['require', 'integer'],
        'sort_order'  => ['require', 'integer'],
        'status'      => ['require', 'integer'],
    ];

    protected $message = [
        'title.max'          => '{%error title}',
        'keywords.max'       => '{%error keywords}',
        'description.max'    => '{%error description}',
        'image.max'          => '{%error image}',
        'type_id.require'    => '{%error type}',
        'type_id.integer'    => '{%error type}',
        'sort_order.require' => '{%error sort_order}',
        'sort_order.integer' => '{%error sort_order}',
        'status.require'     => '{%error status}',
        'status.integer'     => '{%error status}',
    ];
}
