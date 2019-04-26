<?php
/**
 *
 * 验证层
 * 系统设置
 *
 * @package   NICMS
 * @category  app\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
namespace app\validate;

use think\Validate;

class Config extends Validate
{

    protected $rule = [
        'name'  => 'require|max:20',
        'value' => 'require|max:500',
        'lang'  => 'require|max:20'
    ];

    protected $message = [
        'name.require'  => '{%please enter name}',
        'name.max'      => '{%name length shall not exceed 30}',
        'value.require' => '{%please enter value}',
        'value.max'     => '{%value length shall not exceed 500}',
        'lang.require'  => '{%please enter lang}',
        'lang.max'      => '{%lang length shall not exceed 5}',
    ];

    protected $scene = [
    ];
}
