<?php

/**
 *
 * 模板驱动
 *
 * @package   NICMS
 * @category  app\common\library\view
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library\view;

use think\App;
use think\contract\TemplateHandlerInterface;
use app\common\library\DataFilter;
use app\common\library\view\Compiler;
use app\common\library\view\File;
use app\common\library\view\Replace;

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
        'tpl_compile'        => true,                   // 是否开启模板编译,设为false则每次都会重新编译
        'compile_time'       => 28800,                  // 模板编译有效期 0 为永久，(以数字为值，单位:秒)

        'tpl_begin'          => '{',                    // 模板引擎普通标签开始标记
        'tpl_end'            => '}',                    // 模板引擎普通标签结束标记

        'layout_on'          => true,                   // 布局模板开关
        'layout_name'        => 'layout',               // 布局模板入口文件
        'layout_item'        => '{__CONTENT__}',        // 布局模板的内容替换标识

        'tpl_replace_string' => [
            '__THEME__'         => 'theme/',
            '__CSS__'           => 'css/',
            '__IMG__'           => 'img/',
            '__JS__'            => 'js/',
            '__STATIC__'        => 'static/',
            '__NAME__'          => 'NICMS',
            '__TITLE__'         => 'NICMS',
            '__KEYWORDS__'      => 'NICMS',
            '__DESCRIPTION__'   => 'NICMS',
            '__BOTTOM_MSG__'    => 'NICMS',
            '__COPYRIGHT__'     => 'NICMS',
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
     * 模板变量
     * @var array
     */
    private $var_data = [];

    /**
     * 架构函数
     * @access public
     * @param  \think\App $_app
     * @param  array      $_config
     * @return void
     */
    public function __construct(App $app, array $_config = [])
    {
        $this->app = &$app;

        // 编译目录
        $this->config['compile_path'] = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'compile' . DIRECTORY_SEPARATOR;

        // 模板目录
        $this->config['view_path'] = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR;

        // 是否更新编译
        $this->config['tpl_compile'] = (bool) !env('app_debug', false);

        // 合并配置
        $_config = array_filter($_config);
        $this->config = array_merge($this->config, $_config);

        // 当前应用名
        $this->config['app_name'] =
            $this->app->http->getName()
            ? $this->app->http->getName() . DIRECTORY_SEPARATOR
            : '';

        // 分应用存储
        $this->config['compile_path'] .= $this->config['app_name'];
        $this->config['view_path'] .= $this->config['app_name'];
    }

    /**
     * 模板引擎参数赋值
     * @access public
     * @param  array $_config
     * @return void
     */
    public function config(array $_config): void
    {
        unset($_config['compile_path'], $_config['view_path'], $_config['app_name']);

        foreach ($_config as $key => $value) {
            if (is_array($value)) {
                $this->config[$key] = array_merge($this->config[$key], $value);
            } else {
                $this->config[$key] = $value;
            }
        }
        $this->config['view_theme'] = str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            trim($this->config['view_theme'], '\/')
        );

        $this->config['compile_path'] .= $this->config['view_theme'] . DIRECTORY_SEPARATOR;
        $this->config['view_path'] .= $this->config['view_theme'] . DIRECTORY_SEPARATOR;
    }

    /**
     * 获取模板引擎配置
     * @access public
     * @param  string $_name 参数名
     * @return mixed
     */
    public function getConfig(string $_name)
    {
        return $this->config[$_name] ?? null;
    }

    /**
     * 检测是否存在模板文件
     * @access public
     * @param  string $_template 模板文件或者模板规则
     * @return bool
     */
    public function exists(string $_template): bool
    {
        $_template = DataFilter::filter($_template);

        return is_file($_template);
    }

    /**
     * 渲染模板内容
     * @access public
     * @param  string $_content 模板内容
     * @param  array  $_data 模板变量
     * @return void
     */
    public function display(string $_content, array $_data = []): void
    {
        extract($_data, EXTR_OVERWRITE);
        /* eval('?>' . $_content); */
    }

    /**
     * 模板变量赋值
     * @access public
     * @param string|array $name  模板变量
     * @param mixed        $value 变量值
     * @return $this
     */
    public function assign($name, $value = null)
    {
        if (is_array($name)) {
            $this->var_data = array_merge($this->var_data, $name);
        } else {
            $this->var_data[$name] = $value;
        }

        return $this;
    }

    /**
     * 渲染模板文件
     * @access public
     * @param  string $_template 模板文件
     * @param  array  $_data     模板变量
     * @return void
     */
    public function fetch(string $_template, array $_data = []): void
    {
        // 主题设置
        if (is_file($this->config['view_path'] . 'config.json')) {
            $json = file_get_contents($this->config['view_path'] . 'config.json');
            if ($json && $json = json_decode($json, true)) {
                $this->config['tpl_config'] = array_merge($this->config['tpl_config'], (array) $json);
            }
        }



        // 编译
        $compiler = new Compiler([
            'layout_on'    => $this->config['layout_on'],
            'layout_name'  => $this->config['layout_name'],
            'suffix'       => $this->config['compile_suffix'],
            'path'         => $this->config['compile_path'],
            'tpl_compile'  => $this->config['tpl_compile'],
            'compile_time' => $this->config['compile_time'],
        ]);



        // 获取模板文件名
        $_template = DataFilter::filter($_template) . '.' . $this->config['view_suffix'];
        $_template = File::getTheme($this->config['view_path'], $_template);



        // 编译文件
        $compile_file = $compiler->getHashFile($_template);

        // 编译无效 重新模板编译
        if (false === $compiler->check($compile_file)) {
            $content = trim(file_get_contents($_template));

            $replace = new Replace($this->config);
            $replace->getContent($content);

            $compiler->includeFile = File::getIncludeFile();
            $compiler->create($content, $compile_file);
        }


        // 过滤变量内容
        // $_data = DataFilter::encode($_data);
        // $_data = DataFilter::decode($_data);
        $this->var_data = array_merge($this->var_data, $_data);
        extract($this->var_data, EXTR_OVERWRITE);

        //载入模版缓存文件
        include $compile_file;
    }
}
