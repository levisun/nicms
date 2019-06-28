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
    'version'               => '1.0.1 CB9',
    'theme'                 => Env::get('admin.theme', 'default'),
    // 后台入口域名
    'entry'                 => Env::get('admin.entry', 'admin'),

    // 调试
    'debug'                 => Env::get('app.debug', 1) ? true : false,
    // 加密密钥
    'secretkey'             => hash_hmac('sha256', Env::get('app.secretkey', Request::rootDomain()), __DIR__),
    // 上传文件大小,单位MB
    'upload_size'           => Env::get('app.upload_size', '1'),
    // 上传文件类型(扩展名)
    'upload_type'           => Env::get('app.upload_type', 'gif,jpg,png,zip,rar'),

    'api_host'              => '//api.' . Request::rootDomain() . Request::root(),
    'cdn_host'              => '//cdn.' . Request::rootDomain() . Request::root(),

    // 应用地址
    'app_host'              => Env::get('app.host', ''),
    // 应用的命名空间
    'app_namespace'         => '',
    // 是否启用路由
    'with_route'            => true,
    // 是否启用事件
    'with_event'            => true,
    // 自动多应用模式
    'auto_multi_app'        => false,
    // 应用映射（自动多应用模式有效）
    'app_map'               => [],
    // 域名绑定（自动多应用模式有效）
    'domain_bind'           => [],
    // 默认应用
    'default_app'           => 'index',
    // 默认时区
    'default_timezone'      => 'Asia/Shanghai',
    // 异常页面的模板文件
    'exception_tmpl'        => app()->getThinkPath() . 'tpl/think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'         => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'        => Env::get('app.debug', 1) ? true : false,
];
