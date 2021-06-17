<?php

/**
 *
 * API接口层
 * 语言包
 *
 * @package   NICMS
 * @category  app\admin
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    'auth' => [
        'settings'           => '设置',
        'settings_dashboard' => '控制台',
        'settings_basic'     => '网站设置',
        'settings_lang'      => '语言设置',
        'settings_safe'      => '安全设置',

        'theme'              => '主题设置',
        'theme_cms'          => '网站主题',
        'theme_member'       => '会员主题',

        'category'           => '栏目管理',
        'category_category'  => '栏目管理',
        'category_fields'    => '字段管理',
        'category_type'      => '类别管理',
        'category_model'     => '模型管理',

        'content'            => '内容管理',
        'content_article'    => '文章管理',
        'content_link'       => '友链管理',
        'content_feedback'   => '反馈管理',
        'content_message'    => '留言管理',
        'content_banner'     => '幻灯片管理',
        'content_ads'        => '广告管理',
        'content_discuss'    => '评论管理',
        'content_cache'      => '缓存',
        'content_recycle'    => '文章回收站',

        'user'               => '用户',
        'user_user'          => '用户管理',
        'user_level'         => '用户组',
        'user_admin'         => '管理员',
        'user_role'          => '管理员组',
        'user_node'          => '权限节点',

        'book'               => '书籍',
        'book_book'          => '书籍管理',
        'book_article'       => '文章管理',
        'book_type'          => '类别管理',
        'book_author'        => '作者管理',

        'extend'             => '扩展',
        'extend_log'         => '操作日志',
        'extend_databack'    => '数据备份',
        'extend_elog'        => '系统日志',
        'extend_visit'       => '访问日志',
        'extend_addon'       => '扩展插件',
    ],

    'dashboard' => [
        'system info'   => '系统信息',
        'sys version'   => '系统版本',
        'sys os'        => '操作系统',
        'sys sapi'      => '运行模式',
        'sys debug'     => '调试模式',
        'sys env'       => '运行环境',
        'sys db'        => '数据库',
        'sys GD'        => 'gd',
        'sys timezone'  => '时区',
        'sys api'       => 'API地址',
        'sys cdn'       => 'CDN地址',
        'sys lang'      => '语言',
        'sys copyright' => '版权',
        'sys upgrade'   => 'up',
    ],

    'website' => [
        'name'        => '网站名称',
        'keywords'    => '关键词',
        'description' => '描述',
        'footer'      => '底部信息',
        'copyright'   => '版权信息',
        'beian'       => '备案信息',
        'script'      => '执行脚本',
    ],

    'safe' => [
        'app upload max'    => '上传文件大小',
        'app upload type'   => '上传文件类型',
        'database hostname' => '数据库地址',
        'database database' => '数据库名称',
        'database username' => '数据库用户名',
        'database password' => '数据库密码',
        'database hostport' => '数据库端口',
        'database prefix'   => '数据库前缀',
        'cache type'        => '缓存类型',
        'cache expire'      => '缓存时间',
        'app authkey'       => '密钥',
        'admin entry'       => '系统入口',
        'admin theme'       => '系统模板',
        'app debug'         => '调试模式',
        'app maintain'      => '网站维护',
    ],

    'attribute' => [
        'show' => '显示',
        'hide' => '隐藏',
    ],

    'list' => [
        'attribute'       => '属性',
        'backname'        => '备份文件名',
        'category'        => '栏目',
        'date'            => '日期',
        'sort'            => '排序',
        'name'            => '名称',
        'model'           => '模型',
        'type'            => '类型',
        'operation'       => '操作',
        'is_require'      => '必选',
        'table'           => '表',
        'run user'        => '执行用户',
        'run action'      => '执行方法',
        'ip'              => 'IP地址',
        'remark'          => '描述',
        'size'            => '大小',
        'count'           => '统计',
        'status'          => '状态',
        'update time'     => '修改时间',
        'title'           => '标题',
        'access'          => '权限',
        'logo'            => 'LOGO',
        'level'           => '等级',
        'role name'       => '组名',
        'id'              => 'ID',
        'username'        => '用户名',
        'phone'           => '手机号',
        'email'           => '邮箱',
        'last sign in ip' => '登录IP',
        'last login time' => '登录时间',
        'level name'      => '会员等级名',
    ],

    'input' => [
        'sign username' => '用户名,手机号或邮箱',
        'username'      => '用户名',
        'password'      => '密码',
        'verify'        => '验证码',
        'parent'        => '父类',
        'name'          => '名称',
        'aliases'       => '别名',
        'title'         => '标题',
        'keywords'      => '关键词',
        'description'   => '描述',
        'image'         => '图片',
        'type'          => '类型',
        'model'         => '模型',
        'show'          => '显示',
        'channel'       => '频道页',
        'access'        => '权限',
        'url'           => '链接地址',
        'sort'          => '排序',
        'category'      => '栏目',
        'remark'        => '描述',
        'maxlength'     => '最大长度',
        'is_require'    => '必选',
        'status'        => '状态',
        'attribute'     => '属性',
        'pass'          => '审核',
        'origin'        => '源地址',
        'author'        => '作者',
        'thumb'         => '缩略图',
        'width'         => '宽',
        'height'        => '高',
        'image'         => '图片',
        'file'          => '文件',
        'show time'     => '显示时间',
        'content'       => '内容',
        'logo'          => 'LOGO',
        'level'         => '等级',
        'node'          => '节点',
        'node name'     => '节点名',
        'node title'    => '名称',
        'role name'     => '组名',
        'phone'         => '手机号',
        'email'         => '邮箱',
        'not_password'  => '确认密码',
        'level credit'  => '等级积分',
        'level name'    => '会员等级名',
    ],

    'status' => [
        'open'     => '打开',
        'close'    => '关闭',
        'pass'     => '审核',
        'is_pass'  => '已审核',
        'not_pass' => '未审核',
    ],

    'button' => [
        'save'     => '保存',
        'sort'     => '排序',
        'added'    => '添加',
        'remove'   => '删除',
        'editor'   => '编辑',
        'child'    => '子类',
        'yes'      => '是',
        'no'       => '否',
        'select'   => '请选择',
        'sign in'  => '登录',
        'sign out' => '注销',
        'profile'  => '个人信息',
        'databack' => '数据备份',
        'open'     => '打开',
        'pass'     => '审核',
        'is_pass'  => '已审',
        'not_pass' => '未审',
        'commend'  => '推荐',
        'top'      => '置顶',
        'hot'      => '最热',
        'category' => '分类',
        'model'    => '模型',
        'close'    => '关闭',

        'node app type'        => '应用',
        'node controller type' => '分组',
        'node action type'     => '节点名',
        'node method type'     => '方法',
    ],

    'message' => [
        'title' => '标题',
    ],


    'please make a database backup' => '您长期没有进行数据备份，请尽快备份数据',
    'program error message' => '程序出现错误',
    'too much junk information' => '垃圾信息过多，请及时清理',













    'remove cache success' => '缓存清空成功!',

    // 导航
    'category main type' => '主导航',
    'category top type' => '顶部导航',
    'category foot type' => '底部导航',
    'category other type' => '其它导航',
    // 模型
    'article' => '文章',
    'picture' => '图片',
    'download' => '下载',
    'page' => '单页',
    'feedback' => '反馈',
    'message' => '留言',
    'link' => '友链',

    'qingshuru' => '123',
];
