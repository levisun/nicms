<?php
/**
 *
 * 验证层
 * 留言
 *
 * @package   NICMS
 * @category  app\cms\validate\message
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
namespace app\cms\validate\message;

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
        'category_id.require' => '{%error cid}',
        'category_id.integer' => '{%error cid}',


        'captcha.require' => '{%error captcha}',

    ];
}
