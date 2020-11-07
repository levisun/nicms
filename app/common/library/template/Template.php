<?php

declare(strict_types=1);

namespace app\common\library\template;

use think\App;
use think\contract\TemplateHandlerInterface;
use app\common\library\template\Compiler;

class Template implements TemplateHandlerInterface
{
    protected $app;
    protected $data = [];

    // 模板引擎参数
    protected $config = [
        'view_theme'         => '',                     // 模板主题
        'view_path'          => './theme/',             // 模板路径
        'view_suffix'        => 'html',                 // 默认模板文件后缀
        'view_depr'          => DIRECTORY_SEPARATOR,
        // 模板引擎禁用函数
        'tpl_deny_func_list' => 'echo,exit,include,include_once,require,require_once,',
        'tpl_begin'          => '{',                    // 模板引擎普通标签开始标记
        'tpl_end'            => '}',                    // 模板引擎普通标签结束标记
        'tpl_compile'        => true,                   // 是否开启模板编译,设为false则每次都会重新编译
        'compile_path'       => '',
        'compile_suffix'     => 'php',                  // 默认模板编译后缀
        'compile_time'       => 28800,                  // 模板编译有效期 0 为永久，(以数字为值，单位:秒)

        'layout_on'          => true,                   // 布局模板开关
        'layout_name'        => 'layout',               // 布局模板入口文件
        'layout_item'        => '{__CONTENT__}',        // 布局模板的内容替换标识

        'tpl_replace_string' => [],
        'tpl_var_identify'   => 'array', // .语法变量识别，array|object|'', 为空时自动识别
        'default_filter'     => 'htmlentities', // 默认过滤方法 用于普通标签输出

        'strip_space'        => true, // 是否去除模板文件里面的html空格与换行
        'theme_config'       => [],
    ];

    public function __construct(App $_app, array $_config = [])
    {
        $this->app    = $_app;
        $this->config = array_merge($this->config, $_config);

        $this->config['compile_path'] = $this->config['compile_path']
            ?: $this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'compile' . DIRECTORY_SEPARATOR . $this->app->http->getName() . DIRECTORY_SEPARATOR;
    }

    /**
     * 检测是否存在模板文件
     * @access public
     * @param  string $template 模板文件或者模板规则
     * @return bool
     */
    public function exists(string $template): bool
    {
        $compiler = new Compiler($this->config);

        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $template = $compiler->parseTemplateFile($template);
        }

        return is_file($template);
    }

    /**
     * 渲染模板文件
     * @access public
     * @param  string $template 模板文件
     * @param  array  $data     模板变量
     * @return void
     */
    public function fetch(string $_template, array $data = []): void
    {
        $compiler = new Compiler($this->config);

        $_template = $compiler->parseTemplateFile($_template);

        $compiler_file = $this->config['compile_path'] . $this->config['view_theme'] . '_' . md5($this->config['layout_on'] . $this->config['layout_name'] . $_template) . '.' . ltrim($this->config['compile_suffix'], '.');

        if (!$compiler->check($compiler_file)) {
            // 缓存无效 重新模板编译
            $content = file_get_contents($_template);
            $compiler->write($content, $compiler_file);
        }

        // 页面缓存
        ob_start();
        ob_implicit_flush(0);

        if (!empty($vars)) {
            $this->data = array_merge($this->data, $vars);
        }

        $compiler->read($compiler_file, $this->data);

        // 获取并清空缓存
        $content = ob_get_clean();

        echo $content;
    }

    /**
     * 渲染模板内容
     * @access public
     * @param  string $content 模板内容
     * @param  array  $data    模板变量
     * @return void
     */
    public function display(string $content, array $data = []): void
    {
    }

    /**
     * 配置模板引擎
     * @access private
     * @param  array $_config 参数
     * @return void
     */
    public function config(array $_config): void
    {
        $this->config = array_merge($this->config, $_config);
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
}
