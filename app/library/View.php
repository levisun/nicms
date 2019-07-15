<?php

/**
 *
 * 模板驱动
 *
 * @package   NICMS
 * @category  app\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\library;

use think\App;
use think\template\exception\TemplateNotFoundException;
use app\library\DataFilter;

class View
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 模板配置参数
     * @var array
     */
    protected $config = [
        'view_path'          => '',                     // 模板路径
        'view_base'          => '',
        'view_suffix'        => 'html',                 // 默认模板文件后缀
        'view_depr'          => DIRECTORY_SEPARATOR,
        'cache_path'         => '',
        'cache_suffix'       => 'php',                  // 默认模板缓存后缀
        'tpl_deny_func_list' => 'echo,exit',            // 模板引擎禁用函数
        'tpl_deny_php'       => false,                  // 默认模板引擎是否禁用PHP原生代码
        'tpl_begin'          => '{',                    // 模板引擎普通标签开始标记
        'tpl_end'            => '}',                    // 模板引擎普通标签结束标记
        'strip_space'        => false,                  // 是否去除模板文件里面的html空格与换行
        'tpl_cache'          => true,                   // 是否开启模板编译缓存,设为false则每次都会重新编译
        'compile_type'       => 'file',                 // 模板编译类型
        'cache_prefix'       => '',                     // 模板缓存前缀标识，可以动态改变
        'cache_time'         => 0,                      // 模板缓存有效期 0 为永久，(以数字为值，单位:秒)
        'layout_on'          => false,                  // 布局模板开关
        'layout_name'        => 'layout',               // 布局模板入口文件
        'layout_item'        => '{__CONTENT__}',        // 布局模板的内容替换标识
        'taglib_begin'       => '{',                    // 标签库标签开始标记
        'taglib_end'         => '}',                    // 标签库标签结束标记
        'taglib_load'        => true,                   // 是否使用内置标签库之外的其它标签库，默认自动检测
        'taglib_build_in'    => 'cx',                   // 内置标签库名称(标签使用不必指定标签库名称),以逗号分隔 注意解析顺序
        'taglib_pre_load'    => '',                     // 需要额外加载的标签库(须指定标签库名称)，多个以逗号分隔
        'display_cache'      => false,                  // 模板渲染缓存
        'cache_id'           => '',                     // 模板缓存ID
        'tpl_replace_string' => [
            '__THEME__'       => 'theme/',
            '__CSS__'         => 'css/',
            '__IMG__'         => 'img/',
            '__JS__'          => 'js/',
            '__STATIC__'      => 'static/',
            '__NAME__'        => '',
            '__TITLE__'       => '',
            '__KEYWORDS__'    => '',
            '__DESCRIPTION__' => '',
            '__BOTTOM_MSG__'  => '',
            '__COPYRIGHT__'   => '',
        ],
        'tpl_var_identify'   => 'array',                // .语法变量识别，array|object|'', 为空时自动识别
        'default_filter'     => 'htmlentities',         // 默认过滤方法 用于普通标签输出
    ];

    /**
     * 架构函数
     * @access public
     * @param  \think\App $_app
     * @return void
     */
    public function __construct(App $_app)
    {
        $this->app     = $_app;
        $this->config  = $this->app->config;
        $this->lang    = $this->app->lang;
        $this->request = $this->app->request;

        $this->config['view_path'] = $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        $this->config['tpl_replace_string']['__VERSION__'] = $this->config->get('app.version', '1.0.1');
        $this->config['tpl_replace_string']['__STATIC__'] = $this->config->get('app.cdn_host') . '/static/';
    }

    public function parseTemplateFile(string $_template)
    {
        # code...
    }
}
