<?php
/**
 *
 * 验证器
 * 栏目 - 网站栏目
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

class Category extends Validate
{
    protected $rule = [
        'name'        => ['require', 'length:4,20', 'unique:category'],
        'aliases'     => ['max:20', 'unique:category'],
        'title'       => ['max:50'],
        'keywords'    => ['max:100'],
        'description' => ['max:300'],
        'image'       => ['max:100'],
        'type_id'     => ['require', 'number'],
        'model_id'    => ['require', 'number'],
        'is_show'     => ['require', 'number'],
        'is_channel'  => ['require', 'number'],
        'sort_order'  => ['require', 'number'],
        'access_id'   => ['require', 'number'],
        'url'         => ['require', 'max:200'],
    ];

    protected $message = [
        'name.require'       => '{%error catname require}',
        'name.length'        => '{%error catname length not}',
        'name.unique'        => '{%error catname unique}',
        'aliases.length'     => '{%error aliases length not}',
        'aliases.unique'     => '{%error aliases unique}',
        'aliases.alpha'      => '{%error aliases alpha}',
        'title.max'          => '{%error image}',
        'keywords.max'       => '{%error image}',
        'description.max'    => '{%error image}',
        'image.max'          => '{%error image}',
        'type_id.require'    => '{%error type}',
        'type_id.number'     => '{%error type}',
        'model_id.require'   => '{%error model}',
        'model_id.number'    => '{%error model}',
        'is_show.require'    => '{%error model}',
        'is_show.number'     => '{%error model}',
        'is_channel.require' => '{%error model}',
        'is_channel.number'  => '{%error model}',
        'sort_order.require' => '{%error model}',
        'sort_order.number'  => '{%error model}',
        'access_id.require'  => '{%error access}',
        'access_id.number'   => '{%error access}',
        'url.url'            => '{%error url}',
    ];
}
