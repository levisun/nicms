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
use think\facade\Request;

return [
    // 驱动方式
    'type'          => Env::get('cache.type', 'file'),
    // 缓存保存目录
    'path'          => '',
    // 缓存前缀
    'prefix'        => Request::subDomain(),
    // 缓存有效期 0表示永久缓存
    'expire'        => Env::get('cache.expire', 1440),
    'expire'        => 0,
    // 关闭子目录
    'cache_subdir'  => false,
    // 缓存标签前缀
    'tag_prefix'    => 'tag:',
    // 序列化机制 例如 ['serialize', 'unserialize']
    'serialize'     => ['serialize', 'unserialize'],
    // 开启压缩
    'data_compress' => false,
];
