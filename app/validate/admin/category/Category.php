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
        'name'        => ['require', 'length:4,20'],
        'aliases'     => ['max:20'],
        'title'       => ['max:50'],
        'keywords'    => ['max:100'],
        'description' => ['max:300'],
        'image'       => ['max:100'],
        'type_id'     => ['require'],
        'model_id'    => ['require'],
        'is_show'     => ['require'],
        'is_channel'  => ['require'],
        'sort_order'  => ['require'],
        'access_id'   => ['require'],
        'url'         => ['require', 'max:200'],
    ];

    protected $message = [
        'cms_sitename.require' => '{%please enter website name}',
        'cms_sitename.max'     => '{%website name length shall not exceed 500}',
    ];
}
