<?php

/**
 *
 * 模板驱动
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library;

use think\App;
use think\contract\TemplateHandlerInterface;
use think\exception\HttpResponseException;
use think\Response;

class View implements TemplateHandlerInterface
{
    /**
     * 应用实例
     * @var \think\App
     */
    private $app;

    /**
     * 模板配置参数
     * @var array
     */
    private $config = [
        'app_name'           => '',
        'view_path'          => '',                     // 模板路径
        'view_theme'         => '',                     // 模板主题
        'view_suffix'        => 'html',                 // 默认模板文件后缀

        'compile_path'       => '',
        'compile_suffix'     => 'php',                  // 默认模板编译后缀
        'compile_prefix'     => '',                     // 模板编译前缀标识，可以动态改变
        'compile_time'       => 0,                      // 模板编译有效期 0 为永久，(以数字为值，单位:秒)
        'compile_id'         => '',                     // 模板编译ID
        'tpl_compile'        => true,                   // 是否开启模板编译,设为false则每次都会重新编译

        'tpl_begin'          => '{',                    // 模板引擎普通标签开始标记
        'tpl_end'            => '}',                    // 模板引擎普通标签结束标记
        'strip_space'        => true,                   // 是否去除模板文件里面的html空格与换行

        'layout_on'          => true,                   // 布局模板开关
        'layout_name'        => 'layout',               // 布局模板入口文件
        'layout_item'        => '{__CONTENT__}',        // 布局模板的内容替换标识

        'tpl_replace_string' => [
            '{__AUTHORIZATION__}' => '<?php echo create_authorization();?>',
            '{__TOKEN__}'         => '<?php echo token_field();?>',
            '{__REQUEST_PARAM__}' => '<?php echo json_encode(app("request")->param());?>',
            '__THEME__'           => 'theme/',
            '__CSS__'             => 'css/',
            '__IMG__'             => 'img/',
            '__JS__'              => 'js/',
            '__STATIC__'          => 'theme/static/',
            '__NAME__'            => 'NICMS',
            '__TITLE__'           => 'NICMS',
            '__KEYWORDS__'        => 'NICMS',
            '__DESCRIPTION__'     => 'NICMS',
            '__BOTTOM_MSG__'      => 'NICMS',
            '__COPYRIGHT__'       => 'NICMS',
        ],

        'tpl_config' => [
            'api_version'   => '1.0.1',
            'api_appid'     => '1000002',
            'api_appsecret' => '962940cfbe94a64efcd1573cf6d7a175',
        ],
    ];

    /**
     * JS脚本内容
     * @var string
     */
    private $script = '';

    /**
     * 架构函数
     * @access public
     * @param  \think\App $_app
     * @param  array      $_config
     * @return void
     */
    public function __construct(App $app, array $_config = [])
    {
        $this->app = $app;
        // 默认值
        $this->config['compile_path'] = app()->getRuntimePath() . 'compile' . DIRECTORY_SEPARATOR;
        $this->config['view_path'] = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR;
        $this->config['tpl_compile'] = (bool) !env('app_debug', false);
        $this->config['app_name'] = $this->app->http->getName();
        // 合并配置
        $this->config  = array_merge($this->config, $_config);
    }
}
