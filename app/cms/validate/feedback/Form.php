<?php
/**
 *
 * 验证层
 * 反馈
 *
 * @package   NICMS
 * @category  app\cms\validate\feedback
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
namespace app\cms\validate\feedback;

use think\Validate;

class Form extends Validate
{
    protected $rule = [
        'title'       => ['require', 'length: 4,20'],
        'username'    => ['require', 'length: 4,20'],
        'content'     => ['require', 'max: 300'],
        'category_id' => ['require', 'integer'],

        'captcha'  => ['require', 'captcha'],

    ];

    protected $message = [
        'title.require'       => '{%error title require}',
        'title.length'        => '{%error title length not}',
        'username.require'    => '{%error username require}',
        'username.length'     => '{%error username length not}',
        'content.require'     => '{%error content require}',
        'content.length'      => '{%error content length}',
        'category_id.require' => '{%error category_id}',
        'category_id.integer' => '{%error category_id}',


        'captcha.require' => '{%error captcha}',

    ];
}
