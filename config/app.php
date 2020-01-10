<?php

/**
 *
 * 应用设置
 *
 * @package   NiPHP
 * @category  config
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

use think\facade\Env;
use think\facade\Request;

return [
    'version'          => '1.0.1CB200106',
    'theme'            => Env::get('admin.theme', 'default'),
    // 后台入口域名
    'entry'            => Env::get('admin.entry', 'admin'),

    // 调试
    'debug'            => (bool) Env::get('app_debug', false),
    // 加密密钥
    'secretkey'        => hash_hmac('sha256', Env::get('app.secretkey', 'nicms'), __DIR__),
    // 上传文件大小,单位MB
    'upload_size'      => (int) Env::get('app.upload_size', 1),
    // 上传文件类型(扩展名)
    'upload_type'      => Env::get('app.upload_type', 'jpg,gif,png,webp,mp3,mp4,doc,docx,xls,xlsx,ppt,pptx,pdf,zip'),

    // API地址
    'api_host'         => '//api.' . Request::rootDomain(),
    // CDN地址
    'cdn_host'         => '//cdn.' . Request::rootDomain(),
    // IMG地址
    'img_host'         => '//img.' . Request::rootDomain(),



    // 应用地址
    'app_host'         => Env::get('app.host', '//www.' . Request::rootDomain()),
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
        Env::get('admin.entry', 'admin') => 'admin',
        'api'   => 'api',
        'book'  => 'book',
        'my'    => 'user',
        'www'   => 'cms',
        'cdn'   => 'cms',
        'img'   => 'cms',
    ],
    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list'    => ['common'],
    // 默认应用
    'default_app'      => 'cms',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',
    // 异常页面的模板文件
    'exception_tmpl'   => Env::get('app_debug', false)
        ? app()->getThinkPath() . 'tpl/think_exception.tpl'
        : app()->getRootPath() . 'public/404.html',
    // 错误显示信息,非调试模式有效
    'error_message'    => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'   => false,
];
