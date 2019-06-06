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
declare (strict_types = 1);

namespace app\library;

use think\App;
use think\contract\TemplateHandlerInterface;
use think\template\exception\TemplateNotFoundException;

class TemplateE implements TemplateHandlerInterface
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * Lang实例
     * @var \think\Lang
     */
    protected $lang;

    /**
     * request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 主题
     * @var string
     */
    protected $theme;

    /**
     * 主题配置
     * @var array
     */
    protected $theme_config = [];

    protected $content;

    /**
     * 架构函数
     * @access public
     * @param  \think\App $_app
     * @param  array      $_config
     * @return void
     */
    public function __construct(App $_app, array $_config = [])
    {
        $this->app     = $_app;
        $this->lang    = $this->app->lang;
        $this->request = $this->app->request;

        $this->config = array_merge($this->config, $_config);
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
        // 页面缓存
        ob_start();
        ob_implicit_flush(0);



        extract($_data, EXTR_OVERWRITE);
    }

    /**
     * 自动定位模板文件
     * @access private
     * @param  string $_template 模板文件规则
     * @return string
     */
    private function parseTemplate(string $_template): string
    {
        $path = $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;

        // 获取视图根目录
        if (strpos($_template, '@')) {
            // 跨模块调用
            list($app, $_template) = explode('@', $_template, 2);
        }

        $app = isset($app) ? $app : $this->request->controller(true);
        $path .= $app . DIRECTORY_SEPARATOR;
        $path .= $this->theme ? $this->theme . DIRECTORY_SEPARATOR : '';

        $_template = str_replace(['/', ':'], DIRECTORY_SEPARATOR, $_template);
        $_template = $_template ?: $this->request->action(true);
        $_template = ltrim($_template, DIRECTORY_SEPARATOR) . '.html';

        if (is_file($path . 'config.json') && $config = file_get_contents($path . 'config.json')) {
            $this->theme_config = json_decode(strip_tags($config), true);
        } else {
            throw new TemplateNotFoundException('template config not exists:' . $this->theme . 'config.json', $config);
        }

        if ($this->request->isMobile() && is_dir($path . 'mobile' . DIRECTORY_SEPARATOR . $_template)) {
            $path .= 'mobile' . DIRECTORY_SEPARATOR;
        }

        // 模板不存在 抛出异常
        if (!is_file($path . $_template)) {
            throw new TemplateNotFoundException('template not exists:' . $_template, $_template);
        }

        return $path . $_template;
    }
}
