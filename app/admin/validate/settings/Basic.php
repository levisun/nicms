<?php

/**
 *
 * 验证器
 * 设置 - 基础设置
 *
 * @package   NICMS
 * @category  app\admin\validate\settings
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

namespace app\admin\validate\settings;

use think\Validate;

class Basic extends Validate
{
    protected $rule = [
        'cms_sitename' => ['require', 'max:500'],
    ];

    protected $message = [
        'cms_sitename.require' => '{%please enter website name}',
        'cms_sitename.max'     => '{%website name length shall not exceed 500}',
    ];
}
