<?php

/**
 *
 * 缓存设置
 *
 * @package   NiPHP
 * @category  config
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

use think\facade\Env;

$expire = (int) Env::get('cache.expire', 28800) - mt_rand(0, 1440);

return [
    // 默认缓存驱动
    'default' => Env::get('cache.driver', 'file'),
    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'          => 'File',
            // 缓存保存目录
            'path'          => '',
            // 缓存前缀
            'prefix'        => '',
            // 缓存有效期 0表示永久缓存
            'expire'        => $expire,
            // 关闭子目录
            'cache_subdir'  => false,
            // 启用数据压缩
            'data_compress' => true,
            // 缓存标签前缀
            'tag_prefix'    => 'tag: ',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'     => [],
        ],
        // redis缓存
        'redis'   =>  [
            // 驱动方式
            'type'   => 'Redis',
            // 服务器地址
            'host'   => '127.0.0.1',
            // 端口
            'port'   => 6379,
            // 缓存有效期 0表示永久缓存
            'expire' => $expire,
        ],
        // 更多的缓存连接
    ],
];
