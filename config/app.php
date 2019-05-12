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
    'api_host'              => '//api.' . Request::rootDomain() . Request::root(),
    'cdn_host'              => '//cdn.' . Request::rootDomain() . Request::root(),
    'www_host'              => '//www.' . Request::rootDomain() . Request::root(),
    'upload_type'           => Env::get('app.upload_type'),
    'upload_size'           => Env::get('app.upload_size'),
    'debug'                 => Env::get('admin.debug',   1) ? true : false,
    'authkey'               => Env::get('admin.authkey', md5(__DIR__)),
    'entry'                 => Env::get('admin.entry', 'admin'),
    'theme'                 => Env::get('admin.theme', 'default'),
    'version'               => '1.0.1 CB6',

    // 应用地址
    'app_host'              => '',
    // 应用Trace（环境变量优先读取）
    'app_trace'             => Env::get('admin.debug', 1) ? true : false,
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
    // 是否开启多语言
    'lang_switch_on'        => true,
    // 默认语言
    'default_lang'          => 'zh-cn',
    // 默认验证器
    'default_validate'      => '',

    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl' => app()->getThinkPath() . 'tpl/dispatch_jump.tpl',
    'dispatch_error_tmpl'   => app()->getThinkPath() . 'tpl/dispatch_jump.tpl',

    // 异常页面的模板文件
    'exception_tmpl'        => app()->getThinkPath() . 'tpl/think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'         => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'        => Env::get('admin.debug', 1) ? true : false,
];
