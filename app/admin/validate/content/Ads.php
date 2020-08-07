<?php

/**
 *
 * 验证器
 * 内容 - 广告
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

class Ads extends Validate
{
    protected $rule = [
        'name'        => ['require', 'length: 2,50'],
        'width'       => ['require', 'integer'],
        'height'      => ['require', 'integer'],
        'image'       => ['require', 'max:250'],
        'url'         => ['require', 'url', 'max:500'],
        'description' => ['max:250'],
        'is_pass'     => ['integer'],
    ];

    protected $message = [
        'name.require'    => '{%error name require}',
        'name.length'     => '{%error name length not}',
        'width.require'   => '{%error width require}',
        'width.integer'   => '{%error width integer}',
        'height.require'  => '{%error height require}',
        'height.integer'  => '{%error height integer}',
        'image.require'   => '{%error image require}',
        'image.max'       => '{%error image length not}',
        'url.require'     => '{%error url require}',
        'url.url'         => '{%error url}',
        'url.max'         => '{%error url length not}',
        'description.max' => '{%error description length not}',
        'is_pass.integer' => '{%error is_pass}',
    ];
}
