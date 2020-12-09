<?php

/**
 *
 * 应用设置
 *
 * @package   NiPHP
 * @category  config
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    // CB|Alpha 内测版, RC|Beta  正式候选版, Demo 演示版, Stable 稳定版, Release 正式版
    'version'          => '1.0.1RC200412',

    // 调试
    'debug'            => env('app_debug', false),

    // 后台模板
    'theme'            => env('admin.theme', 'default'),
    // 后台入口域名
    'entry'            => env('admin.entry', 'admin'),

    // 上传设置
    'upload_size'      => env('app.upload_size', 3),
    'upload_type'      => env('app.upload_type', 'jpg,gif,png,webp,mp3,mp4,doc,docx,xls,xlsx,ppt,pptx,pdf,zip'),

    // API CDN IMG地址
    'api_host'         => '//api.' . request()->rootDomain() . '/',
    'cdn_host'         => '//cdn.' . request()->rootDomain() . '/',
    'img_host'         => '//img.' . request()->rootDomain() . '/',

    // URL加密密钥(不可修改,否则会出现无法修复的错误)
    'url62secret'      => env('app.url62secret', '02GcWtlRUHhixEqokMBue1FPbJsZfOLTa4DjpIrg5KC38NmS9nV7d6QwAzXYyv'),
    // 加密密钥(不可修改,否则会出现无法修复的错误)
    'secretkey'        => hash_hmac('sha256', env('app.secretkey', 'nicms'), sha1(request()->rootDomain() . __DIR__)),

    // 应用地址
    'app_host'         => env('app.host', '//www.' . request()->rootDomain()),
    // 应用的命名空间
    'app_namespace'    => '',
    // 是否启用路由
    'with_route'       => true,
    // 是否启用事件
    'with_event'       => true,
    // 自动多应用模式
    'auto_multi_app'   => true,
    // 应用映射（自动多应用模式有效）
    'app_map'          => [],
    // 域名绑定（自动多应用模式有效）
    'domain_bind'      => [
        env('admin.entry', 'admin') => 'admin',
        'api'  => 'api',
        'book' => 'book',
        'my'   => 'user',
        'www'  => 'cms',
        'm'    => 'cms',
        'cdn'  => 'cms',
        'img'  => 'cms',
    ],
    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list'    => ['common'],
    // 默认应用
    'default_app'      => 'cms',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',
    // 异常页面的模板文件
    'exception_tmpl'   => env('app_debug', false)
        ? app()->getThinkPath() . 'tpl/think_exception.tpl'
        : app()->getRootPath() . 'public/static/error.html',
    // 错误显示信息,非调试模式有效
    'error_message'    => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'   => false,
];
