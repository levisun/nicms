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

return [
    // API接口地址
    'api_host'              => '//api.' . request()->rootDomain() . request()->root(),
    // CDN地址
    'cdn_host'              => '//cdn.' . request()->rootDomain() . request()->root(),
    // 主站地址
    'www_host'              => '//www.' . request()->rootDomain() . request()->root(),
    // 后台管理地址
    'admin_host'            => '//admin.' . request()->rootDomain() . request()->root(),
    // 调试模式
    'app_debug'             => env('app.app_debug', 1) ? true : false,
    // 密钥
    'authkey'               => env('app.authkey', md5(__DIR__)),

    // 应用地址
    'app_host'              => '',
    // 应用Trace（环境变量优先读取）
    'app_trace'             => env('app.app_debug', 1) ? true : false,
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
    'show_error_msg'        => env('app.app_debug', 1) ? true : false,
];
