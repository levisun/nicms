<?php

/**
 *
 * 应用开始
 *
 * @package   NICMS
 * @category  app\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\event;

use think\App;

class AppStart
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * ??
     */
    protected $path = '';

    public function handle(App $_app)
    {
        $this->app     = $_app;
        $this->request = $this->app->request;
        $this->path    = $this->app->getRuntimePath() . 'lock' . DIRECTORY_SEPARATOR;

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }

        $this->inspect();
    }

    /**
     * 检查空间环境支持
     * @access protected
     * @param
     * @return void
     */
    protected function inspect(): void
    {
        $lock = $this->path . 'inspect.lock';
        if (!is_file($lock) || filemtime($lock) >= strtotime('-30 days')) {
            version_compare(PHP_VERSION, '7.1.0', '>=') or die('系统需要PHP7.1+版本! 当前PHP版本:' . PHP_VERSION . '.');
            version_compare(app()->version(), '6.0.0RC4', '>=') or die('系统需要ThinkPHP 6.0+版本! 当前ThinkPHP版本:' . app()->version() . '.');
            extension_loaded('pdo') or die('请开启 pdo 模块!');
            extension_loaded('pdo_mysql') or die('请开启 pdo_mysql 模块!');
            class_exists('ZipArchive') or die('空间不支持 ZipArchive 方法,系统备份功能无法使用.');
            function_exists('file_put_contents') or die('空间不支持 file_put_contents 函数,系统无法写文件.');
            function_exists('fopen') or die('空间不支持 fopen 函数,系统无法读写文件.');
            get_extension_funcs('gd') or die('空间不支持 gd 模块,图片打水印和缩略生成功能无法使用.');
            file_put_contents($lock, '环境支持');
        }
    }
}
