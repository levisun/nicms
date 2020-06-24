<?php

/**
 *
 * 插件
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\common\library;

class Addon
{

    /**
     * 添加插件
     * @access public
     * @static
     * @return array
     */
    public static function added()
    {
        # code...
    }

    /**
     * 删除插件
     * @access public
     * @static
     * @return array
     */
    public static function remove()
    {
        # code...
    }

    /**
     * 更新插件
     * @access public
     * @static
     * @return array
     */
    public static function update()
    {
        # code...
    }

    /**
     * 查询所有插件
     * @access public
     * @static
     * @return array
     */
    public static function query()
    {
        $path = root_path('extend/addon');
        is_dir($path) or mkdir($path, 0755, true);
        $path .= 'addon.json';

        file_put_contents($path, json_encode([
            'name' => '插件列表',
            'require' => [
                'baidu/ziyuan' => 'open',
                'baidu/tongji' => 'close',
                'lazyload'     => 'open',
            ],
        ], JSON_UNESCAPED_UNICODE));

        $addon = file_get_contents($path);
        $addon = json_decode($addon, true);
        $addon = $addon['require'];

        $dir = root_path('extend/addon');
        foreach ($addon as $path => $status) {
            $file = $dir . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path) . DIRECTORY_SEPARATOR . 'config.json';
            if (is_file($file) && $config = file_get_contents($file)) {
                if ($config = json_decode($config, true)) {
                    $addon[$path] = [
                        'status'  => $status,
                        'name'    => empty($config['name']) ?: $config['name'],
                        'version' => empty($config['version']) ?: $config['version'],
                        'date'    => empty($config['date']) ?: $config['date'],
                    ];
                    continue;
                }
            }

            $addon[$path] = 'down';
        }

        return $addon;
    }

    /**
     * 运行
     * @access public
     * @static
     * @return array
     */
    public static function exec(string &$_method, string &$_content): string
    {
        $_method = trim($_method, '\/');

        // 校验配置文件
        $file  = root_path('extend/addon');
        $file .= str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $_method) . DIRECTORY_SEPARATOR . 'config.json';
        if (is_file($file) && $config = file_get_contents($file)) {
            if ($config = json_decode($config, true)) {
                // 校验方法是否存在
                $class = '\addon\\' . str_replace(['/', '\\'], '\\', $_method) . '\Addon';
                if (class_exists($class)) {
                    if (method_exists($class, 'run')) {
                        $result = (string) call_user_func([app($class, [$config, $_content]), 'run']);
                        return $result ?: $_content;
                    }
                }
            }
        }

        return $_content;
    }
}
